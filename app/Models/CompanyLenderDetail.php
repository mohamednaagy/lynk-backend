<?php

namespace App\Models;

use App\Enums\CompanyMarketType;
use Illuminate\Database\Eloquent\Model;

class CompanyLenderDetail extends Model
{
    protected $casts = [
        'preferred_market_type' => CompanyMarketType::class,
    ];

    protected $fillable = [
        'default_contract_sign_time_limit',
        'force_preferred_commodity_type',
        'company_id',
        'notifications_email',
        'company_cr',
        'contract_number',
        'preferred_market_type',
    ];

    protected $casts = [
        'force_preferred_commodity_type' => 'boolean',
    ];

    public function lender()
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }
}
