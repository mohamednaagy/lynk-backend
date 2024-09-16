<?php

namespace App\Models;

use App\Enums\CommoitySupplierStatus;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class Supplier extends Company
{
    protected static function newFactory(): Factory
    {
        return SupplierFactory::new();
    }

    public function locations()
    {
        return $this->hasMany(SupplierLocation::class, 'company_id');
    }

    public function commodityItems()
    {
        return $this->hasMany(CommodityItem::class, 'company_id');
    }

    public function detail()
    {
        return $this->hasOne(CompanySupplierDetail::class, 'company_id');
    }

    /**
     * Scope a query to include suppliers with a specific status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSupplierStatus($query, $status)
    {
        return $query->whereHas('detail', function ($query) use ($status) {
            $query->where('status', $status);
        });
    }
}
