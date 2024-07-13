<?php

namespace App\Models;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class LocalMarketInventory extends Model
{
    use HasFactory, LogsActivity;

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
        'status' => LocalMarketInventoryStatus::class,
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
        if ($this->reserved_items > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
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
        $this->available_quantity = $this->units()->where('status', LocalMarketInventoryUnitsStatus::Free)->count();
        $this->reserved_items = $this->units()->where('status', LocalMarketInventoryUnitsStatus::Reserved)->count();

        $this->save();
    }
}
