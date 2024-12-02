<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class LocalMarketLive extends Model
{
    protected $table = 'local_market_live';

    protected $fillable = [
        'inventory_id',
        'commodity_item_id',
        'commodity_type_id',
        'supplier_location_id',
        'supplier_id',
        'company_id',
        'price',
        'eligible_quantity',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    // Relationships
    public function inventory()
    {
        return $this->belongsTo(LocalMarketInventory::class, 'inventory_id');
    }

    public function commodityItem()
    {
        return $this->belongsTo(CommodityItem::class, 'commodity_item_id');
    }

    public function commodityType()
    {
        return $this->belongsTo(CommodityType::class, 'commodity_type_id');
    }

    public function supplierLocation()
    {
        return $this->belongsTo(SupplierLocation::class, 'supplier_location_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
