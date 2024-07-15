<?php

namespace App\Models;

use App\Enums\LocalMarketInventoryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LocalMarketInventory extends Model
{
    use HasFactory , LogsActivity;

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

    public function canUpdateUnits($total_new_units) 
    {
        if($total_new_units > $this->reserved_items) {
            return true;
        }
        return false;
    }
}
