<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Traits\HasWalletFloat;
use Bavix\Wallet\Traits\HasWallets;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\HasScopedValidationRules;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Company extends BaseTenant implements Wallet
{
    use HasFactory, HasScopedValidationRules, HasWallet, HasWallets, HasWalletFloat, SoftDeletes;

    protected $table = 'companies';

    public $incrementing = true;

    protected $dates = ['deleted_at'];

    protected $casts = [
        'status' => CompanyStatus::class,
        'does_order_require_approval' => 'boolean',
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
            'webhook_url',
            'webhook_secret_key',
            'webhook_type',
            'created_at',
            'updated_at',
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
}
