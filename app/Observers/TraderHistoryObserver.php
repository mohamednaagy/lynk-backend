<?php

namespace App\Observers;

use App\Jobs\ApplyOrderFeesJob;
use App\Models\TraderHistory;
use App\Observers\Traits\ObserverHelper;
use App\Services\TraderOrder\FeesService;
use App\Services\TraderOrder\StepDurationService;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;

class TraderHistoryObserver implements ShouldHandleEventsAfterCommit
{
    use ObserverHelper;

    public function __construct(
        private readonly FeesService $feesService,
        private readonly StepDurationService $stepDurationService
    ) {}

    /**
     * @throws \Exception
     */
    public function created(TraderHistory $traderHistory)
    {
        try {
            $traderOrder = $traderHistory->traderOrder()->first();

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('TraderHistoryObserver::created START', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
            ]);

            Trader::driver($traderOrder->provider, $traderOrder->version)
                ->dispatchJobForTransitioningFlow($traderOrder);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('TraderHistoryObserver::created - Job dispatched, getting step nodes', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
            ]);

            $currentStepNode = app(StepHistoriesDictionary::class)->getStepByHistory($traderHistory->action);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('TraderHistoryObserver::created - Current step node retrieved', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
                'current_step_node' => $currentStepNode ? get_class($currentStepNode) : null,
            ]);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('TraderHistoryObserver::created - Admins notified, getting completed step node', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
            ]);

            $currentCompletedStepNode = app(StepHistoriesDictionary::class)->getCompletedStepByHistory($traderHistory->action);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('TraderHistoryObserver::created - Completed step node retrieved', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
                'completed_step_node' => $currentCompletedStepNode ? get_class($currentCompletedStepNode) : null,
            ]);

            $this->fireWebhookWhenStatusIsMurabhaOfferIssued($traderOrder, $currentCompletedStepNode);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('TraderHistoryObserver::created - Webhook fired, processing provider actions', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
                'provider' => $traderOrder->provider,
            ]);

            $providerActions = $this->getActionsOfProvider($traderOrder->provider, $currentCompletedStepNode);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('TraderHistoryObserver::created - Provider actions retrieved', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
                'provider_actions_count' => count($providerActions),
                'provider_actions' => $providerActions,
            ]);

            foreach ($providerActions as $actionClass) {
                app($actionClass)->handle($traderOrder->order, $traderHistory->traderOrder);
            }

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('TraderHistoryObserver::created - All provider actions completed, applying order fees', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
            ]);

            $this->applyOrderFees($traderHistory);
            $this->stepDurationService->setStepDuration($traderOrder, $traderHistory->action);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('TraderHistoryObserver::created - Order fees applied, updating cached last history action', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
                'last_history_action' => $traderOrder->last_history_action,
            ]);
        } catch (\Exception $e) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('TraderHistoryObserver::created failed', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'traderHistoryId' => $traderHistory->id,
                'action' => $traderHistory->action,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle the status change of the TraderHistory.
     */
    protected function applyOrderFees(TraderHistory $traderHistory): void
    {
        Log::channel(getSuitableLoggingFromTraderProvider($traderHistory->traderOrder))->info(formatLogTitle('TraderHistoryObserver::applyOrderFees', $traderHistory->traderOrder), [
            'financingOrderId' => $traderHistory->traderOrder->financing_order_id,
            'traderOrderId' => $traderHistory->traderOrder->id,
            'traderHistoryId' => $traderHistory->id,
            'action' => $traderHistory->action,
            'action_class' => $this->feesService->getAction($traderHistory->traderOrder->provider, $traderHistory->action),
        ]);
        $provider = $traderHistory->traderOrder->provider;
        $status = $traderHistory->action;
        $action = $this->feesService->getAction($provider, $status);
        if ($action) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderHistory->traderOrder))->info(formatLogTitle('TraderHistoryObserver dispatching applyOrderFees job', $traderHistory->traderOrder), [
                'trader_order_id' => $traderHistory->traderOrder->id,
            ]);

            ApplyOrderFeesJob::dispatch($traderHistory->traderOrder, $action);
        }
    }
}
