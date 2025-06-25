<?php

namespace App\Models;

use App\Enums\CompanyMarketType;
use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Enums\TraderOrderMode;
use Illuminate\Database\Eloquent\Model;

class CompanyLenderDetail extends Model
{
    protected $casts = [
        'preferred_market_type' => CompanyMarketType::class,
        'force_preferred_commodity_type' => 'boolean',
        'does_order_require_approval' => 'boolean',
        'notify_admins_about_new_orders' => CompanyNewOrderNotificationForAdminStatus::class,
        'notify_borrowers_about_order_updates' => 'boolean',
        'force_unique_reference_number' => 'boolean',
        'require_initiate_trade_request' => 'boolean',
        'trading_mode' => TraderOrderMode::class,
        'auto_complete_murabaha_order' => 'boolean',
        'webhook_secret_key' => 'encrypted',
        'allow_preferred_commodity_in_order' => 'boolean',
    ];

    protected $fillable = [
        'default_contract_sign_time_limit',
        'force_preferred_commodity_type',
        'company_id',
        'notifications_email',
        'force_unique_reference_number',
        'company_cr',
        'contract_number',
        'preferred_market_type',
        'does_order_require_approval',
        'notify_admins_about_new_orders',
        'notify_borrowers_about_order_updates',
        'require_initiate_trade_request',
        'trading_mode',
        'internal_status_comment',
        'public_status_comment',
        'auto_complete_murabaha_order',
        'webhook_secret_key',
        'allow_preferred_commodity_in_order',
        'token_expire_in',
    ];

    public function lender()
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }
}
