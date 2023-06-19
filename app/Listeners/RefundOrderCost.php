<?php

namespace App\Listeners;

use App\Actions\Contracts\Orders\RefundOrderCreationFees;
use App\Enums\TraderOrderStatus;
use App\Events\OrderCancelled;

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
     * @param  \App\Events\OrderCancelled  $event
     * @return void
     */
    public function handle(OrderCancelled $event)
    {
        $traderOrder = $event->traderOrder;

        if ($traderOrder->inCommodityPurchasingStep() === false && $traderOrder->status->is(TraderOrderStatus::PurchasingFailure)) {
            app(RefundOrderCreationFees::class)->handle($traderOrder);
        }
    }
}
