<?php

namespace App\Observers;

use App\Enums\FinancingOrderHistory;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOtcCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificate;

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
        if ($traderOrder->provider == 'bursam') {
            if ($traderOrder->doesLastActionMatchWith(FinancingOrderHistory::MurabahaSaleCompleted)) {
                ProcessBursamOtcCertificate::dispatch($traderOrder->id);
                ProcessBursamStbCertificate::dispatch($traderOrder->id);
            }
        }
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
