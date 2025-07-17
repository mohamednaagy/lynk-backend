<?php

namespace App\Models;

use App\Enums\CompanyMarketType;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\OrderFeeType;
use App\Enums\Trader;
use App\Support\QueryScoper\HasScopes;
use App\Support\Wallets\Traits\HasWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Stancl\Tenancy\Database\Concerns\HasScopedValidationRules;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Company extends BaseTenant
{
    use HasFactory, HasScopedValidationRules, HasScopes, HasWallet, LogsActivity, SoftDeletes;

    protected $table = 'companies';

    public $incrementing = true;

    protected $casts = [
        'status' => CompanyStatus::class,
        'type' => CompanyType::class,
        'deleted_at' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'unique_name',
            'status',
            'type',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status']);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FinancingOrder::class, 'company_id', 'id');
    }

    public function traderOrders(): HasMany
    {
        return $this->hasMany(TraderOrder::class, 'provider', 'driver');
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class, 'company_id');
    }

    public function wallets()
    {
        return $this->morphMany(Wallet::class, 'holder');
    }

    public function walletNotification()
    {
        return $this->hasOne(WalletNotification::class, 'company_id')->ofMany();
    }

    public function tieredPricing()
    {
        return $this->hasMany(TieredPricing::class, 'company_id');
    }

    public function isTiered(): bool
    {
        return $this->tieredPricing()->count() > 1 || $this->tieredPricing()->first()->fee_type->is(OrderFeeType::Proration);
    }

    public function isStandard(): bool
    {
        return $this->isTiered() === false;
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSelectTraderOrdersCountBySubquery($query)
    {
        return $query->addSelect([
            'orders_count' => TraderOrder::selectRaw('COUNT(DISTINCT financing_order_id) as orders_count')
                ->whereColumn('provider', 'companies.driver')
                ->limit(1),
        ]);
    }

    public function commoditySupplier()
    {
        return $this->hasOne(CompanySupplierDetail::class, 'company_id');
    }

    public function supplier()
    {
        return $this->hasOne(Supplier::class, 'id');
    }

    public function commodityTypes()
    {
        return $this->belongsToMany(CommodityType::class, 'company_commodity_types', 'company_id', 'commodity_type_id');
    }

    public function isInternationalMarketType()
    {
        return $this->lender->lenderDetail->preferred_market_type->is(CompanyMarketType::International());
    }

    public function getPreferredTrader()
    {
        if ($this->isInternationalMarketType()) {
            return Trader::Bursam;
        }

        return Trader::Lynk;
    }

    public function isCompanyHasMurabahaAutoCompleteOrder()
    {
        return $this->lender->lenderDetail->auto_complete_murabaha_order;
    }

    public function lender()
    {
        return $this->hasOne(Lender::class, 'id');
    }

    public function getTokenExpireValue()
    {
        return $this->lender->lenderDetail->token_expire_in;
    }

    public function getTokenExpireVersion(): int
    {
        return (int) $this->lender->lenderDetail->token_version;
    }

    /**
     * This function is a temporary workaround to avoid removing the HasDataColumn trait from the Tenant model.
     * It will be removed once we upgrade to version 4 of the package.
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();
        unset($attributes['data']);

        return $attributes;
    }
}
