<?php

namespace App\Models;

use App\Enums\LocalMarket\InventoryStatus;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LocalMarketInventory extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'commodity_item_id',
        'commodity_type_id',
        'supplier_location_id',
        'company_id',
        'min_price',
        'max_price',
        'reserved_items',
        'available_quantity',
        'status',
    ];

    protected $casts = [
        'status' => InventoryStatus::class,
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
        return $this->belongsTo(CommodityType::class, 'commodity_type_id');
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

    public function getIsEditableAttribute()
    {
        //save for later if business changes and want to update inventory with a specified criteriea
        return true;
    }

    public function canUpdateUnits($total_new_units)
    {
        if ($total_new_units >= $this->reserved_items) {
            return true;
        }

        if ($total_new_units == $this->total_items) {
            return false;
        }

        return false;
    }

    /**
     * Determine if the item is deletable.
     * An item is considered deletable if there are no reserved items.
     */
    public function getIsDeletableAttribute(): bool
    {
        return $this->reserved_items == 0;
    }

    /*
     * Check if the company has bought from this inventory before.
     *
     * @param  int  $companyId
     * @return bool
     */
    public function hasCompanyBoughtFromInventory($companyId)
    {
        return $this->whereHas('localMarketOrderHasInventory', function ($query) use ($companyId) {
            $query->where('supplier_id', $companyId);
        })
            ->exists();
    }

    public function price()
    {
        return $this->max_price;
    }

    /**
     * Update the available quantity based on the number of free units in LocalMarketInventoryUnits.
     *
     * @return void
     */
    public function refreshStockQuantities()
    {
        $this->available_quantity = $this->units()->where('status', InventoryUnitsStatus::Free)->count();
        $this->reserved_items = $this->units()->where('status', InventoryUnitsStatus::Reserved)->count();

        $this->save();
    }
}
