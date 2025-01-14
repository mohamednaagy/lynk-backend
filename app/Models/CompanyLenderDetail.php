<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyLenderDetail extends Model
{
    protected $fillable = [
        'default_contract_sign_time_limit',
        'company_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
