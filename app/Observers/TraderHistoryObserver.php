<?php

namespace App\Observers;

use App\Models\TraderHistory;
use App\Observers\Traits\ObserverHelper;
use App\Services\TraderOrder\FeesService;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
use Illuminate\Support\Facades\Log;

class TraderHistoryObserver
{
    use ObserverHelper;

    public function __construct(private FeesService $feesService) {}

    /**
     * @throws \Exception
     */
    public function created(TraderHistory $traderHistory)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('TraderHistoryObserver::created failed', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderHistory->trader_order_id,
                'action' => $traderHistory->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle the status change of the TraderHistory.
     */
    protected function applyOrderFees(TraderHistory $traderHistory): void
    {
        Log::info('TraderHistoryObserver::applyOrderFees', [
            'trader_history_id' => $traderHistory->id,
            'trader_order_id' => $traderHistory->trader_order_id,
            'action' => $traderHistory->action,
            'action_class' => $this->feesService->getAction($traderHistory->traderOrder->provider, $traderHistory->action),
        ]);
        $provider = $traderHistory->traderOrder->provider;
        $status = $traderHistory->action;
        $action = $this->feesService->getAction($provider, $status);
        if ($action) {
            $action->handle($traderHistory->traderOrder);
        }
    }
}
