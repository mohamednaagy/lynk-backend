<?php

namespace App\Observers;

use App\Models\TraderHistory;
use App\Observers\Traits\ObserverHelper;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;

class TraderHistoryObserver
{
    use ObserverHelper;

    public function created(TraderHistory $traderHistory)
    {
        $traderOrder = $traderHistory->traderOrder()
            ->withLastHistoryAction()
            ->first();

        Trader::driver($traderOrder->provider, $traderOrder->version)
            ->dispatchJobForTransitioningFlow($traderOrder);

        $currentStepNode = app(StepHistoriesDictionary::class)->getStepByHistory($traderHistory->action);
        $this->notifyAdminsAboutOrderStopped($traderHistory, $currentStepNode);

        $currentCompletedStepNode = app(StepHistoriesDictionary::class)->getCompletedStepByHistory($traderHistory->action);
        $this->fireWebhookWhenStatusIsMurabhaOfferIssued($traderOrder, $currentCompletedStepNode);

        foreach ($this->getActionsOfProvider($traderOrder->provider, $currentCompletedStepNode) as $action) {
            app($action)->handle($traderOrder->order, $traderHistory->traderOrder);
        }
    }

    /**
     * Handle the TraderHistory "updated" event.
     *
     * @return void
     */
    public function updated(TraderHistory $traderHistory)
    {
        //
    }

    /**
     * Handle the TraderHistory "deleted" event.
     *
     * @return void
     */
    public function deleted(TraderHistory $traderHistory)
    {
        //
    }

    /**
     * Handle the TraderHistory "restored" event.
     *
     * @return void
     */
    public function restored(TraderHistory $traderHistory)
    {
        //
    }

    /**
     * Handle the TraderHistory "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(TraderHistory $traderHistory)
    {
        //
    }
}
