<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CommodityItem extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'unique_name',
        'description',
        'company_id',
        'commodity_type_id',
        'min_price',
        'max_price',
        'volume_sellable_unit',
        'currency_id',
        'measurement_id',
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

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(LocalMarketInventory::class);
    }

    public function getAvailableUnitsAttribute(): int
    {
        return $this->inventories()->sum('available_quantity');
    }

    public function getReservedUnitsAttribute(): int
    {
        return $this->inventories()->sum('reserved_items');
    }

    /**
     * Determine if the inventory is deletable.
     *
     * An inventory is deletable if the sum of reserved units is zero.
     */
    public function getIsDeletableAttribute(): bool
    {
        return $this->reserved_units == 0;
    }
}
