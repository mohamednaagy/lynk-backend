<?php

namespace App\Listeners;

use App\Actions\Contracts\Orders\RefundOrderCreationFees;
use App\Events\OrderCancelled;
use App\Models\TraderOrder;
use Illuminate\Support\Facades\DB;

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
            $traderOrder = TraderOrder::lockForUpdate()->findOrFail($event->traderOrder);

            if ($traderOrder->isCommodityPurchased() === false) {
                app(RefundOrderCreationFees::class)->handle($traderOrder);
            }
        });
    }
}
