<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMarketOrderHasInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_market_order_id',
        'inventory_id',
        'quantity',
        'price',
        'measurement_id',
        'currency_id',
        'location_id',
        'supplier_id',
        'previous_owner',
        'commodity_item_id',
        'commodity_type_id',
    ];

    public function inventory()
    {
        return $this->belongsTo(LocalMarketInventory::class, 'inventory_id');
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class, 'measurement_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function location()
    {
        return $this->belongsTo(SupplierLocation::class, 'location_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Company::class, 'supplier_id');
    }

    public function commodityItem()
    {
        return $this->belongsTo(CommodityItem::class, 'commodity_item_id');
    }

    public function commodityType()
    {
        return $this->belongsTo(CommodityType::class, 'commodity_type_id');
    }

    public function traderOrderUnits()
    {
        return $this->hasMany(LocalMarketTraderOrderUnit::class, 'order_has_inventory_id');
    }
}
