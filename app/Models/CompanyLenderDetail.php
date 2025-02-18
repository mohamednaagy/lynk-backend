<?php

namespace App\Models;

use App\Enums\CompanyMarketType;
use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use Illuminate\Database\Eloquent\Model;

class CompanyLenderDetail extends Model
{
    protected $casts = [
        'preferred_market_type' => CompanyMarketType::class,
        'does_order_require_approval' => 'boolean',
        'notify_admins_about_new_orders' => CompanyNewOrderNotificationForAdminStatus::class,
    ];

    protected $fillable = [
        'default_contract_sign_time_limit',
        'company_id',
        'notifications_email',
        'company_cr',
        'contract_number',
        'preferred_market_type',
        'does_order_require_approval',
        'notify_admins_about_new_orders',
    ];

    public function lender()
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }
}
