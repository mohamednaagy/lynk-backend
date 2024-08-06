<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_identifier',
        'name',
        'description',
        'created_at',
        'company_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function inventories() {
        return $this->hasMany(LocalMarketInventory::class, 'supplier_location_id');
    }

    /**
     * @return int
     */
    public function getAvailableUnitsAttribute() :int
    {
        return $this->inventories()->sum('available_quantity');
    }

    /**
     * @return int
     */
    public function getReservedUnitsAttribute() :int
    {
        return $this->inventories()->sum('reserved_items');
    }

    /**
     * Determine if the inventory is deletable.
     *
     * An inventory is deletable if the sum of reserved units is zero.
     *
     * @return bool
     */
    public function getIsDeletableAttribute(): bool
    {
        return $this->reserved_units == 0;
    }
}
