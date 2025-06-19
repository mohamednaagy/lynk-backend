<?php

namespace App\Models;

use App\Enums\CommodityTypeProvider;
use App\Enums\CommodityTypeStatus;
use App\Enums\CompanyMarketType;
use App\Enums\Trader;
use App\Enums\TraderOrderMode;
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
     * Scope a query to filter commodities based on the company's trading setup.
     *
     * - If the company's trading mode is manual, return all commodities.
     * - If it's automatic:
     *     - Return commodities matching the preferred market type.
     *     - If the preferred market type is "any", return all commodities.
     */
    public function scopeGetCommoditiesBasedOnCompany(Builder $query, int $value): Builder
    {
        $company = Company::with(['lender.lenderDetail'])->find($value);
        $lenderDetail = $company->lender->lenderDetail;
        if ($lenderDetail->trading_mode->is(TraderOrderMode::Automatic)) {
            return match (true) {
                $lenderDetail->preferred_market_type->is(CompanyMarketType::Local()) => $query->where('provider', Trader::Lynk),
                $lenderDetail->preferred_market_type->is(CompanyMarketType::International()) => $query->where('provider', Trader::Bursam),
                default => $query,
            };
        }

        return $query;
    }
}
