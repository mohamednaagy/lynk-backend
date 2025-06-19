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
            Log::info('TraderHistoryObserver::created START', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderHistory->trader_order_id,
                'action' => $traderHistory->action,
            ]);

            Log::info('TraderHistoryObserver::created - Getting trader order', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderHistory->trader_order_id,
                'action' => $traderHistory->action,
            ]);

            $traderOrder = $traderHistory->traderOrder()
                ->withLastHistoryAction()
                ->first();

            Log::info('TraderHistoryObserver::created - Trader order retrieved', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
                'provider' => $traderOrder->provider,
                'version' => $traderOrder->version,
            ]);

            Log::info('TraderHistoryObserver::created - Dispatching job for transitioning flow', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
                'provider' => $traderOrder->provider,
            ]);

            Trader::driver($traderOrder->provider, $traderOrder->version)
                ->dispatchJobForTransitioningFlow($traderOrder);

            Log::info('TraderHistoryObserver::created - Job dispatched, getting step nodes', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
            ]);

            $currentStepNode = app(StepHistoriesDictionary::class)->getStepByHistory($traderHistory->action);

            Log::info('TraderHistoryObserver::created - Current step node retrieved', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
                'current_step_node' => $currentStepNode ? get_class($currentStepNode) : null,
            ]);

            $this->notifyAdminsAboutOrderStopped($traderHistory, $currentStepNode);

            Log::info('TraderHistoryObserver::created - Admins notified, getting completed step node', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
            ]);

            $currentCompletedStepNode = app(StepHistoriesDictionary::class)->getCompletedStepByHistory($traderHistory->action);

            Log::info('TraderHistoryObserver::created - Completed step node retrieved', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
                'completed_step_node' => $currentCompletedStepNode ? get_class($currentCompletedStepNode) : null,
            ]);

            $this->fireWebhookWhenStatusIsMurabhaOfferIssued($traderOrder, $currentCompletedStepNode);

            Log::info('TraderHistoryObserver::created - Webhook fired, processing provider actions', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
                'provider' => $traderOrder->provider,
            ]);

            $providerActions = $this->getActionsOfProvider($traderOrder->provider, $currentCompletedStepNode);

            Log::info('TraderHistoryObserver::created - Provider actions retrieved', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
                'provider_actions_count' => count($providerActions),
                'provider_actions' => $providerActions,
            ]);

            foreach ($providerActions as $actionClass) {
                Log::info('TraderHistoryObserver::created - Executing provider action', [
                    'trader_history_id' => $traderHistory->id,
                    'trader_order_id' => $traderOrder->id,
                    'action' => $traderHistory->action,
                    'action_class' => $actionClass,
                ]);

                app($actionClass)->handle($traderOrder->order, $traderHistory->traderOrder);

                Log::info('TraderHistoryObserver::created - Provider action executed', [
                    'trader_history_id' => $traderHistory->id,
                    'trader_order_id' => $traderOrder->id,
                    'action' => $traderHistory->action,
                    'action_class' => $actionClass,
                ]);
            }

            Log::info('TraderHistoryObserver::created - All provider actions completed, applying order fees', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
            ]);

            $this->applyOrderFees($traderHistory);

            Log::info('TraderHistoryObserver::created - Order fees applied, COMPLETED SUCCESSFULLY', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderOrder->id,
                'action' => $traderHistory->action,
            ]);

        } catch (\Exception $e) {
            Log::error('TraderHistoryObserver::created failed', [
                'trader_history_id' => $traderHistory->id,
                'trader_order_id' => $traderHistory->trader_order_id,
                'action' => $traderHistory->action,
                'error' => $e->getMessage(),
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
