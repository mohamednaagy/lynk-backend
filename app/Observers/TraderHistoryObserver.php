<?php

namespace App\Observers;

use App\Jobs\FinancingOrders\NotifyAdminsAboutOrderDelayed;
use App\Models\TraderHistory;

class TraderHistoryObserver
{
    /**
     * Handle the TraderHistory "created" event.
     *
     * @param  \App\Models\TraderHistory  $traderHistory
     * @return void
     */
    public function created(TraderHistory $traderHistory)
    {
        NotifyAdminsAboutOrderDelayed::dispatch($traderHistory->traderOrder)
            ->delay(now()->addMinutes(5));
    }

    /**
     * Handle the TraderHistory "updated" event.
     *
     * @param  \App\Models\TraderHistory  $traderHistory
     * @return void
     */
    public function updated(TraderHistory $traderHistory)
    {
        //
    }

    /**
     * Handle the TraderHistory "deleted" event.
     *
     * @param  \App\Models\TraderHistory  $traderHistory
     * @return void
     */
    public function deleted(TraderHistory $traderHistory)
    {
        //
    }

    /**
     * Handle the TraderHistory "restored" event.
     *
     * @param  \App\Models\TraderHistory  $traderHistory
     * @return void
     */
    public function restored(TraderHistory $traderHistory)
    {
        //
    }

    /**
     * Handle the TraderHistory "force deleted" event.
     *
     * @param  \App\Models\TraderHistory  $traderHistory
     * @return void
     */
    public function forceDeleted(TraderHistory $traderHistory)
    {
        //
    }
}
