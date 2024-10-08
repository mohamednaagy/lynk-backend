<?php

namespace App\Models;

use App\Enums\TraderOrderCancelReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TraderOrderCancelDetail extends Model
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
        'cancel_reason' => TraderOrderCancelReason::class,
    ];

    public function traderOrder()
    {
        return $this->belongsTo(TraderOrder::class);
    }

    public function cancelMessage(): ?string
    {
        return match ($this->cancel_reason->value) {
            TraderOrderCancelReason::FinancingOrderIsCancelled => __('order.trader.cancel_message.'.TraderOrderCancelReason::FinancingOrderIsCancelled),
            TraderOrderCancelReason::TraderOrderIsCancelled => __('order.trader.cancel_message.'.TraderOrderCancelReason::TraderOrderIsCancelled),
            TraderOrderCancelReason::NoEligibleCommoditiesAvailable => __('order.trader.cancel_message.'.TraderOrderCancelReason::NoEligibleCommoditiesAvailable),
            default => null,
        };
    }
}
