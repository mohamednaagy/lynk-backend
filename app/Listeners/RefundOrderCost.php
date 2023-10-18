<?php

namespace App\Listeners;

use App\Actions\Contracts\Orders\RefundOrderCreationFees;
use App\Enums\TraderOrderRefundReason;
use App\Enums\TraderOrderStatus;
use App\Events\TraderOrderCancelled;
use App\Models\TraderOrder;
use Illuminate\Support\Facades\DB;

class RefundOrderCost
{
    const ONE_DAY = 24 * 60 * 60;

    const THREE_DAYS = 3 * 24 * 60 * 60;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(TraderOrderCancelled $event)
    {
        DB::multipleTransaction(function () use ($event) {
            $traderOrder = TraderOrder::lockForUpdate()
                ->findOrFail($event->traderOrder->id);

            $order = $traderOrder->order;

            $baseTraderOrder = $order->traderOrders()
                ->where('is_base', true)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $refundReason = $this->resolveRefundReason($baseTraderOrder, $traderOrder);

            if ($refundReason !== false) {
                app(RefundOrderCreationFees::class)->handle($traderOrder, $refundReason);
            }
        });
    }

    /**
     * @throws \Exception
     */
    private function resolveRefundReason(
        TraderOrder $baseTraderOrder,
        TraderOrder $traderOrder,
    ): int|false {
        $cancelledAt = now();

        $secondsSinceCreation = $cancelledAt->diffInSeconds($baseTraderOrder->created_at);

        if (
            $secondsSinceCreation <= static::ONE_DAY
        ) {
            return TraderOrderRefundReason::WITHIN_24_HOUR;
        }

        if ($secondsSinceCreation >= static::THREE_DAYS) {
            return false;
        }

        $order = $traderOrder->order;
        $latestNonRefundedTraderOrder = $order->traderOrders()
            ->where('status', TraderOrderStatus::Cancelled)
            ->where('data->cancelled_at', '>=', $baseTraderOrder->created_at->addSeconds(static::ONE_DAY))
            ->where('data->cancelled_at', '<=', $baseTraderOrder->created_at->addSeconds(static::THREE_DAYS))
            ->whereNull('data->refunded_at')
            ->where('id', '!=', $traderOrder->id)
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($latestNonRefundedTraderOrder) {
            return TraderOrderRefundReason::WITHIN_72_HOUR;
        }

        return false;
    }
}
