<?php

namespace App\Models;

use App\Enums\FinancingOrderProceedCase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TraderOrderProceedCase extends Model
{
    use HasFactory , SoftDeletes;

    protected $fillable = [
        'trader_order_id',
        'case',
    ];

    protected $casts = [
        'case' => FinancingOrderProceedCase::class,
    ];
}
