<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyLenderDetail extends Model
{
    protected $fillable = [
        'default_contract_sign_time_limit',
        'force_preferred_commodity_type',
        'company_id',
        'notifications_email',
    ];

    protected $casts = [
        'force_preferred_commodity_type' => 'boolean',
    ];

    public function lender()
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }
}
