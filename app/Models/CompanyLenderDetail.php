<?php

namespace App\Models;

use App\Enums\CompanyMarketType;
use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Enums\FinancialProductEnum;
use App\Enums\TraderOrderMode;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CompanyLenderDetail extends Model
{
    protected $casts = [
        'preferred_market_type' => CompanyMarketType::class,
        'does_order_require_approval' => 'boolean',
        'notify_admins_about_new_orders' => CompanyNewOrderNotificationForAdminStatus::class,
        'force_unique_reference_number' => 'boolean',
        'require_initiate_trade_request' => 'boolean',
        'trading_mode' => TraderOrderMode::class,
        'auto_complete_murabaha_order' => 'boolean',
        'webhook_secret_key' => 'encrypted',
        'allow_preferred_commodity_in_order' => 'boolean',
        'default_contract_sign_time_limit' => 'integer' ,
        'allowed_financial_products' => 'array' 
    ];

    protected $fillable = [
        'default_contract_sign_time_limit',
        'company_id',
        'notifications_email',
        'force_unique_reference_number',
        'company_cr',
        'contract_number',
        'preferred_market_type',
        'does_order_require_approval',
        'notify_admins_about_new_orders',
        'require_initiate_trade_request',
        'trading_mode',
        'internal_status_comment',
        'public_status_comment',
        'auto_complete_murabaha_order',
        'webhook_secret_key',
        'allow_preferred_commodity_in_order',
        'token_expire_in',
        'token_version',
        'allowed_financial_products',
    ];

    public function lender()
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }

    // public function setAllowedFinancialProductsAttribute($value)
    // {
    //     $values = is_array($value) ? $value : explode(',', (string) $value);
    //     foreach ($values as $v) {
    //         $v = (int) $v;
    //         if (! FinancialProductEnum::hasValue($v)) {
    //             throw new InvalidArgumentException("Invalid financing product: {$v}");
    //         }
    //     }
    //     $this->attributes['allowed_financial_products'] = implode(',', $values);
    // }

    // public function getAllowedFinancialProductsAttribute($value)
    // {
    //     if (is_array($value)) {
    //         return array_map('intval', $value);
    //     }
    //     return array_map('intval', explode(',', $value));
    // }

    public function getDefaultfinancialProductIdAttribute()
    {
        return $this->allowed_financial_products[0] ?? FinancialProductEnum::NormalLending;
    }
}
