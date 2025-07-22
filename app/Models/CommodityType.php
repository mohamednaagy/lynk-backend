<?php

namespace App\Models;

use App\Enums\CommodityTypeProvider;
use App\Enums\CommodityTypeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CommodityType extends Model
{
    use HasFactory , LogsActivity , SoftDeletes;

    protected $fillable = [
        'name',
        'unique_name',
        'description',
        'status',
        'provider',

    ];

    protected $casts = [
        'status' => CommodityTypeStatus::class,
        'provider' => CommodityTypeProvider::class,

    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status']);
    }

    public function scopeActive(Builder $query, ?int $value): Builder
    {
        return match ($value) {
            1 => $query->where('status', CommodityTypeStatus::Active()),
            2 => $query->where('status', CommodityTypeStatus::Inactive()),
            default => $query,
        };
    }

    /**
     * Scope a query to filter commodities based on the company's allowed commodity types.
     *
     * This scope filters commodity types based on the company's allowed commodity types.
     *
     * @param  Builder  $query  The query builder instance
     * @param  int  $value  The company ID to filter by
     * @return Builder The filtered query based on company's trading preferences
     */
    public function scopeGetCommoditiesBasedOnCompany(Builder $query, int $value): Builder
    {
        $company = Company::with(['lenderOrderAllowedCommodityTypes'])->find($value);
        $commodityTypeIds = $company->lenderOrderAllowedCommodityTypes->pluck('id');
        $query = $query->whereIn('id', $commodityTypeIds);

        return $query;
    }

    /**
     * Get the companies that are allowed to use this commodity type in their orders.
     *
     * This relationship is defined through the pivot table 'company_lender_order_allowed_commodity_types'
     * which maps commodity types to companies that can use them.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function companiesWithOrderAccess()
    {
        return $this->belongsToMany(Company::class, 'company_lender_order_allowed_commodity_types', 'commodity_type_id', 'company_id');
    }
}
