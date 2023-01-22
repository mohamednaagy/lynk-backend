<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Support\Money\Casts\MoneyStringCast;
use App\Support\Wallets\Traits\HasWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\HasScopedValidationRules;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Company extends BaseTenant
{
    use HasFactory, HasScopedValidationRules, SoftDeletes, HasWallet;

    protected $table = 'companies';

    public $incrementing = true;

    protected $dates = ['deleted_at'];

    protected $casts = [
        'status' => CompanyStatus::class,
        'does_order_require_approval' => 'boolean',
        'webhook_secret_key' => 'encrypted',
        'order_cost' => MoneyStringCast::class.':order_cost_currency',
        'type' => CompanyType::class,
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'unique_name',
            'company_cr',
            'status',
            'public_status_comment',
            'internal_status_comment',
            'does_order_require_approval',
            'order_cost',
            'webhook_secret_key',
            'created_at',
            'updated_at',
            'order_cost_currency',
            'type',
            'driver',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FinancingOrder::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function scopeTraderType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
