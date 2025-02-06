<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyLenderDetail extends Model
{
    protected $fillable = [
        'default_contract_sign_time_limit',
        'company_id',
        'notifications_email',
        'company_cr',
        'contract_number',
    ];

    public function lender()
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }
}
