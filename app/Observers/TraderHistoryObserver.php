<?php

namespace App\Observers;

use App\Jobs\FinancingOrders\NotifyAdminsIfTraderOrderHasStopped;
use App\Models\TraderHistory;
use App\Settings\Classes\GeneralSettings;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Stancl\Tenancy\Database\TenantScope;

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
        $financingOrder = $traderHistory->traderOrder
            ->order()
            ->withoutGlobalScope(TenantScope::class)
            ->first();
        $financingOrderStatus = $financingOrder->status->value;

        $timeout = app(GeneralSettings::class)->trader_order_timeout;
        // TO DO
        // some Order at last step so no next step I think  another mail content needed
        $nextStepNode = app(StepHistoriesDictionary::class)->getNextStepOf($financingOrderStatus);
        if ($nextStepNode) {
            NotifyAdminsIfTraderOrderHasStopped::dispatch($traderHistory->traderOrder, $financingOrderStatus)
                ->delay(now()->addMinutes($timeout));
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
