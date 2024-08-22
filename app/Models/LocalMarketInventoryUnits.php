<?php

namespace App\Models;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LocalMarketInventoryUnits extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'inventory_id',
        'commodity_item_id',
        'qr_code',
        'status',
    ];

    protected $casts = [
        'status' => InventoryUnitsStatus::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public function item()
    {
        return $this->belongsTo(CommodityItem::class, 'commodity_item_id');
    }

    public function inventory()
    {
        return $this->belongsTo(LocalMarketInventory::class, 'local_market_inventory_id');
    }

    public function traderOrderUnits()
    {
        return $this->hasMany(LocalMarketOrderHasUnit::class, 'inventory_unit_id');
    }

    public function unitRotations()
    {
        return $this->hasMany(LocalMarketUnitRotation::class, 'inventory_unit_id');
    }
}
