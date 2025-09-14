<?php

namespace App\Models;

use App\Enums\LocalMarket\InventoryStatus;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Jobs\LocalMarket\InventoryEligibleQuantities\RebuildInventory;
use App\Services\LocalMarket\EligibleQuantityService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LocalMarketInventory extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'commodity_item_id',
        'supplier_location_id',
        'company_id',
        'reserved_items',
        'available_quantity',
        'status',
        'commodity_type_id',
        'is_editable',
    ];

    protected $casts = [
        'status' => InventoryStatus::class,
        'is_editable' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'company_id');
    }

    public function localMarketOrderHasInventory()
    {
        return $this->hasMany(LocalMarketOrderHasInventory::class, 'local_market_inventory_id');
    }

    public function type()
    {
        return $this->hasOneThrough(
            CommodityType::class,
            CommodityItem::class,
            'id',
            'id',
            'commodity_item_id',
            'commodity_type_id'
        );
    }

    public function item()
    {
        return $this->belongsTo(CommodityItem::class, 'commodity_item_id');
    }

    public function location()
    {
        return $this->belongsTo(SupplierLocation::class, 'supplier_location_id');
    }

    public function getTotalItemsAttribute()
    {
        return $this->available_quantity + $this->reserved_items;
    }

    // Method to generate QR Code Base Name
    public function generateQrCodeBaseName()
    {
        $type = substr($this->type->unique_name, 0, 2);
        $itemId = substr($this->item->unique_name, 0, 2);

        return $type.'-'.$itemId;
    }

    public function units()
    {
        return $this->hasMany(LocalMarketInventoryUnits::class, 'local_market_inventory_id');
    }

    public function CountOfUnits()
    {
        return count($this->units);
    }

    public function canUpdateUnits($total_new_units)
    {
        return $total_new_units >= $this->reserved_items;
    }

    /**
     * Determine if the item is deletable.
     * An item is considered deletable if there are no reserved items.
     */
    public function getIsDeletableAttribute(): bool
    {
        return $this->reserved_items == 0 && $this->is_editable;
    }

    public function price()
    {
        return $this->item->max_price;
    }

    /**
     * Update the available quantity based on the number of free units in LocalMarketInventoryUnits.
     *
     * @param  bool  $forceRebuildEligibility  If true, rebuild eligibility immediately
     * @return void
     */
    public function refreshStockQuantities($forceRebuildEligibility = false)
    {
        if (! $this->canBeEditable()) {
            return;
        }

        $currentAvailableQuantity = $this->available_quantity;
        $currentReservedItems = $this->reserved_items;
        $updated = $this->updateQuantities();
        $this->refresh();
        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info('Refreshing stock quantities for inventory: ', [
            'inventory_id' => $this->id,
            'current_available_quantity' => $currentAvailableQuantity,
            'current_reserved_items' => $currentReservedItems,
            'new_available_quantity' => $this->available_quantity,
            'new_reserved_items' => $this->reserved_items,
            'updated' => $updated,
            'forceRebuildEligibility' => $forceRebuildEligibility,
        ]);
        if ($forceRebuildEligibility) {
            app(EligibleQuantityService::class)->rebuildForInventory($this);
        } else {
            RebuildInventory::dispatch($this->id);
        }
    }

    public function updateQuantities()
    {
        return DB::update("
        UPDATE local_market_inventories
        SET
            available_quantity = (
                SELECT COUNT(*)
                FROM local_market_inventory_units
                USE INDEX (idx_units_status_optimized, idx_units_count_covering)
                WHERE local_market_inventory_units.local_market_inventory_id = local_market_inventories.id
                AND local_market_inventory_units.status = ?
                AND local_market_inventory_units.deleted_at IS NULL
            ),
            reserved_items = (
                SELECT COUNT(*)
                FROM local_market_inventory_units
                USE INDEX (idx_units_status_optimized, idx_units_count_covering)
                WHERE local_market_inventory_units.local_market_inventory_id = local_market_inventories.id
                AND local_market_inventory_units.status = ?
                AND local_market_inventory_units.deleted_at IS NULL
            ),
            updated_at = NOW()
        WHERE id = {$this->id}
    ", [
            InventoryUnitsStatus::Free,
            InventoryUnitsStatus::Reserved,
        ]);
    }

    public function canBeEditable(): bool
    {
        return $this->is_editable;
    }

    public function markAsEditable(): void
    {
        $this->update([
            'is_editable' => 1,
        ]);
    }
}
