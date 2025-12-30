<?php

namespace App\Models;

use App\Enums\CommodityTypeStatus;
use App\Enums\LocalMarket\SupplierStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommodityItem extends Model
{
    use HasFactory, SoftDeletes;

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

    /**
     * Scope a query to filter commodity-items by their "active" status.
     *
     * @param  Builder  $query  The query builder instance.
     * @param  int|null  $value  The active filter value:
     *                           1 = Active: Both the supplier and commodity type are active.
     *                           2 = Inactive: Either the supplier or the commodity type is inactive.
     *                           3/null = No filter applied; return all commodity-items.
     */
    public function scopeActive(Builder $query, ?int $value): Builder
    {
        return match ($value) {
            // Case 1: Both supplier and commodity-type must be active.
            1 => $query
                ->whereHas('supplier', fn ($q) => $q->whereHas('detail', fn ($q) => $q->where('status', SupplierStatus::Active))
                )->whereHas('type', fn ($q) => $q->where('status', CommodityTypeStatus::Active)
                ),
            // Case 2: Either supplier or commodity-type must be inactive.
            2 => $query->where(function ($q) {
                $q->whereHas('supplier', fn ($q) => $q->whereHas('detail', fn ($q) => $q->where('status', SupplierStatus::Inactive()))
                )->orWhereHas('type', fn ($q) => $q->where('status', CommodityTypeStatus::Inactive)
                );
            }),
            // Default Case: No filter applied (active = 3 or null).
            default => $query,
        };
    }
}
