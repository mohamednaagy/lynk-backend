<?php

namespace App\Listeners;

use App\Actions\Contracts\Orders\RefundOrderCreationFees;
use App\Enums\TraderOrderRefundReason;
use App\Events\OrderCancelled;
use App\Models\TraderOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundOrderCost
{
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
    public function handle(OrderCancelled $event)
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

            $latestRefundedTraderOrder = $order->traderOrders()
                ->where('data->refunded_at', '>=', $baseTraderOrder->created_at->addHours(24))
                ->latest('id')
                ->lockForUpdate()
                ->first();

            try {
                $refundReason = $this->resolveRefundReason($baseTraderOrder, $latestRefundedTraderOrder);

                app(RefundOrderCreationFees::class)->handle($traderOrder, $refundReason);
            } catch (\Exception $exception) {
                Log::debug($exception->getMessage(), [
                    'financing_order_id' => $order->id,
                    'trader_order_id' => $traderOrder->id,
                    'base_trader_order' => $baseTraderOrder->id,
                    'cancelled_at' => now(),
                ]);
            }
        });
    }

    /**
     * @throws \Exception
     */
    private function resolveRefundReason(
        TraderOrder $baseTraderOrder,
        ?TraderOrder $latestRefundedTraderOrder,
    ): int {
        $cancelledAt = now();

        if (
            $baseTraderOrder->created_at->greaterThan($cancelledAt->subHours(24))
        ) {
            return TraderOrderRefundReason::WITHIN_24_HOUR;
        } elseif (
            $baseTraderOrder->created_at->greaterThan($cancelledAt->subHours(72))
            && $latestRefundedTraderOrder
        ) {
            return TraderOrderRefundReason::WITHIN_72_HOUR;
        }

        throw new \Exception('Order cannot be refunded');
    }
}
