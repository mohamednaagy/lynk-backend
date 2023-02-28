<?php

namespace App\Observers;

use App\Enums\FinancingOrderHistory;
use App\Jobs\FinancingOrders\NotifyAdminsIfTraderOrderHasStopped;
use App\Models\TraderHistory;

class TraderHistoryObserver
{
    /**
     * Handle the TraderHistory "created" event.
     *
     * @param  TraderHistory  $traderHistory
     * @return void
     */
    public function created(TraderHistory $traderHistory)
    {
        $financingOrderStatus = $traderHistory->traderOrder->order->status->value;

        if ($traderHistory->action == FinancingOrderHistory::$orderHistoryLastActionMap[$financingOrderStatus]) {
            NotifyAdminsIfTraderOrderHasStopped::dispatchSync($traderHistory->traderOrder);
//                ->delay(now()->addMinutes(5));
        }
    }

    /**
     * Handle the TraderHistory "updated" event.
     *
     * @param  TraderHistory  $traderHistory
     * @return void
     */
    public function updated(TraderHistory $traderHistory)
    {
        //
    }

    /**
     * Handle the TraderHistory "deleted" event.
     *
     * @param  TraderHistory  $traderHistory
     * @return void
     */
    public function deleted(TraderHistory $traderHistory)
    {
        //
    }

    /**
     * Handle the TraderHistory "restored" event.
     *
     * @param  TraderHistory  $traderHistory
     * @return void
     */
    public function restored(TraderHistory $traderHistory)
    {
        //
    }

    /**
     * Handle the TraderHistory "force deleted" event.
     *
     * @param  TraderHistory  $traderHistory
     * @return void
     */
    public function forceDeleted(TraderHistory $traderHistory)
    {
        //
    }
}
