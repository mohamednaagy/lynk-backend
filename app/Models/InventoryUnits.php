<?php

namespace App\Models;

use App\Enums\InventoryUnitsStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryUnits extends Model
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
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
