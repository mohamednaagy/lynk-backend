<?php

namespace App\Models;

use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\OrderFeeType;
use App\Enums\TraderOrderMode;
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
    use HasFactory, HasScopedValidationRules, SoftDeletes, HasWallet, LogsActivity, HasScopes;

    protected $table = 'companies';

    public $incrementing = true;

    protected $dates = ['deleted_at'];

    protected $casts = [
        'status' => CompanyStatus::class,
        'does_order_require_approval' => 'boolean',
        'webhook_secret_key' => 'encrypted',
        'type' => CompanyType::class,
        'notify_admins_about_new_orders' => CompanyNewOrderNotificationForAdminStatus::class,
        'trading_mode' => TraderOrderMode::class,
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'notifications_email',
            'unique_name',
            'company_cr',
            'status',
            'public_status_comment',
            'internal_status_comment',
            'does_order_require_approval',
            'webhook_secret_key',
            'created_at',
            'updated_at',
            'type',
            'driver',
            'notify_admins_about_new_orders',
            'trading_mode',
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
        return $this->hasMany(FinancingOrder::class);
    }

    public function traderOrders(): HasMany
    {
        return $this->hasMany(TraderOrder::class, 'provider', 'driver');
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function wallets()
    {
        return $this->morphMany(Wallet::class, 'holder');
    }

    public function tieredPricing()
    {
        return $this->hasMany(TieredPricing::class);
    }

    public function isTiered(): bool
    {
        return $this->tieredPricing()->count() > 1 || $this->tieredPricing()->first()->fee_type->is(OrderFeeType::Proration);
    }

    public function isStandard(): bool
    {
        return $this->tieredPricing()->count() == 1 && $this->tieredPricing()->first()->fee_type->is(OrderFeeType::Fixed);
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
}
