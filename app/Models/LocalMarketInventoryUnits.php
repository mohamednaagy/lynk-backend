<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LocalMarketInventoryUnits extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'inventory_id',
        'commodity_item_id',
        'qr_code',
        'status',
        'hold_for',
        'current_owner',
        'current_owner_type',
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

    public static function insertBulk($data)
    {
        $now = saudi_now();
        // Implement bulk insertion logic here
        $data = array_map(function ($item) use ($now) {
            return array_merge($item, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $data);

        DB::table((new static)->getTable())->insert($data);
    }
}
