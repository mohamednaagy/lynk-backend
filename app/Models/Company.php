<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Traits\HasWalletFloat;
use Bavix\Wallet\Traits\HasWallets;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Stancl\Tenancy\Database\Concerns\HasScopedValidationRules;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Company extends BaseTenant implements Wallet
{
    use HasFactory;
    use HasScopedValidationRules;
    use HasWallet;
    use HasWallets;
    use HasWalletFloat;
    use SoftDeletes;

    protected $table = 'companies';

    public $incrementing = true;

    protected $dates = ['deleted_at'];

    protected $casts = [
        'status' => CompanyStatus::class,
        'does_order_require_approval' => 'boolean',
        'webhook_secret_key' => 'encrypted',
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

    public function webhookSecretKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Crypt::decryptString($value),
        );
    }
}
