<?php

namespace App\Observers;

use App\Models\TraderHistory;
use App\Observers\Traits\ObserverHelper;
use App\Services\TraderOrder\FeesService;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;

class TraderHistoryObserver
{
    use ObserverHelper;

    public function __construct(private FeesService $feesService) {}

    /**
     * @throws \Exception
     */
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

        foreach ($this->getActionsOfProvider($traderOrder->provider, $currentCompletedStepNode) as $actionClass) {
            app($actionClass)->handle($traderOrder->order, $traderHistory->traderOrder);
        }

        $this->applyOrderFees($traderHistory);
    }

    /**
     * Handle the status change of the TraderHistory.
     */
    protected function applyOrderFees(TraderHistory $traderHistory): void
    {
        $provider = $traderHistory->traderOrder->provider;
        $status = $traderHistory->action;
        $action = $this->feesService->getAction($provider, $status);
        if ($action) {
            $action->handle($traderHistory->traderOrder);
        }
    }
}
