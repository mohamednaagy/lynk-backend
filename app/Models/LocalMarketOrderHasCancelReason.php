<?php

namespace App\Models;

use App\Enums\LocalMarketOrderCancelReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMarketOrderHasCancelReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancelled_by',
        'cancel_reason',
        'cancel_step',
        'trader_order_id',
        'cancel_type',
    ];

    protected $casts = [
        'cancel_reason' => LocalMarketOrderCancelReason::class,
    ];

    public function traderOrder()
    {
        return $this->belongsTo(TraderOrder::class);
    }
}
