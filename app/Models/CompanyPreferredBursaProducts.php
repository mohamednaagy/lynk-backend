<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyPreferredBursaProducts extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'trader_product_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
