<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_identifier',
        'name',
        'description',
        'created_at',
        'company_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function inventories()
    {
        return $this->hasMany(LocalMarketInventory::class, 'supplier_location_id');
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
