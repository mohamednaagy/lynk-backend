<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientAutoSellPeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_lender_client_id',
        'effective_start',
        'effective_end',
    ];
}
