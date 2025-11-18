<?php

namespace App\Models;

use App\Enums\FinancingOrderProceedCase;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TraderOrderProceedCase extends Model
{
    use HasCreator , HasFactory, SoftDeletes;

    protected $fillable = [
        'trader_order_id',
        'case',
        'creator_id',
    ];

    protected $casts = [
        'case' => FinancingOrderProceedCase::class,
    ];
}
