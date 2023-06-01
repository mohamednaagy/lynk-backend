<?php

namespace App\Observers;

use App\Models\TraderOrder;

class TraderOrderObserver
{
    /**
     * Handle the TraderOrder "created" event.
     *
     * @param  \App\Models\TraderOrder  $traderOrder
     * @return void
     */
    public function created(TraderOrder $traderOrder)
    {
        //
    }

    /**
     * Handle the TraderOrder "updated" event.
     *
     * @param  \App\Models\TraderOrder  $traderOrder
     * @return void
     */
    public function updated(TraderOrder $traderOrder)
    {
    }

    /**
     * Handle the TraderOrder "deleted" event.
     *
     * @param  \App\Models\TraderOrder  $traderOrder
     * @return void
     */
    public function deleted(TraderOrder $traderOrder)
    {
        //
    }

    /**
     * Handle the TraderOrder "restored" event.
     *
     * @param  \App\Models\TraderOrder  $traderOrder
     * @return void
     */
    public function restored(TraderOrder $traderOrder)
    {
        //
    }

    /**
     * Handle the TraderOrder "force deleted" event.
     *
     * @param  \App\Models\TraderOrder  $traderOrder
     * @return void
     */
    public function forceDeleted(TraderOrder $traderOrder)
    {
        //
    }
}
