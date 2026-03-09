<?php

namespace App\Models;

use App\Enums\CompanyMarketType;
use App\Enums\TraderOrderMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

/**
 * Summary of CompanyLenderDetail
 *
 * @property int $id
 * @property float|null $min_wallet_limit
 *
 * @mixin Builder<CompanyLenderDetail>
 */
class CompanyLenderDetail extends Model
{
    use Rememberable;

    protected $rememberCacheTag = 'company_lender_details';

    protected $rememberCachePrefix = 'company_lender_details';

    protected $rememberFor = 60 * 60;

    protected $casts = [
        'preferred_market_type' => CompanyMarketType::class,
        'does_order_require_approval' => 'boolean',
        'force_unique_reference_number' => 'boolean',
        'require_initiate_trade_request' => 'boolean',
        'trading_mode' => TraderOrderMode::class,
        'auto_complete_murabaha_order' => 'boolean',
        'webhook_secret_key' => 'encrypted',
        'allow_preferred_commodity_in_order' => 'boolean',
        'default_contract_sign_time_limit' => 'integer',
        'allowed_financing_order_types' => 'array',
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
        'require_initiate_trade_request',
        'trading_mode',
        'internal_status_comment',
        'public_status_comment',
        'auto_complete_murabaha_order',
        'webhook_secret_key',
        'allow_preferred_commodity_in_order',
        'token_expire_in',
        'token_version',
        'allowed_financing_order_types',
        'min_wallet_limit',
    ];

    public function lender()
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }
}
