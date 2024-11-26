<?php

namespace App\Models;

use App\Enums\TraderOrderTimeLimitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TraderOrderTimeLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'default_value',
        'effective_at',
        'trader_order_id',
    ];

    protected $casts = [
        'type' => TraderOrderTimeLimitType::class,
        'effective_at' => 'datetime',
    ];

    public function traderOrder()
    {
        return $this->belongsTo(TraderOrder::class);
    }
}
