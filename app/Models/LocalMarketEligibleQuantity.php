<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalMarketEligibleQuantity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'inventory_id',
        'company_id',
        'eligible_quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the inventory that owns the eligible quantity.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(LocalMarketInventory::class, 'inventory_id');
    }

    /**
     * Get the company that owns the eligible quantity.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Scope a query to filter by inventory.
     */
    public function scopeByInventory($query, $inventoryId)
    {
        return $query->where('inventory_id', $inventoryId);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by minimum eligible quantity.
     */
    public function scopeMinimumQuantity($query, $quantity)
    {
        return $query->where('eligible_quantity', '>=', $quantity);
    }
}
