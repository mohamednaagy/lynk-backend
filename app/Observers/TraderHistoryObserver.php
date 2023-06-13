<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Jobs\FinancingOrders\NotifyAdminsIfTraderOrderHasStopped;
use App\Models\TraderHistory;
use App\Settings\Classes\GeneralSettings;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
use Illuminate\Support\Facades\Log;

class TraderHistoryObserver
{
    /**
     * Handle the TraderHistory "created" event.
     *
     * @param  TraderHistory  $traderHistory
     * @return void
     *
     * @throws \Exception
     */
    public bool $afterCommit = true;

    public function created(TraderHistory $traderHistory)
    {
        $traderOrder = $traderHistory->traderOrder()->withLastHistoryAction()->first();
        Log::debug('observer', [$traderOrder->last_history_action]);
        Trader::driver($traderOrder->provider, $traderOrder->version)
            ->dispatchJobForTransitioningFlow($traderOrder);

        $timeout = app(GeneralSettings::class)->trader_order_timeout;

        // some Order at last step so no next step I think  another mail content needed
        $currentStepNode = app(StepHistoriesDictionary::class)->getStepByHistory($traderHistory->action);

        if (! $currentStepNode) {
            return;
        }

        $nextStepNode = app(StepHistoriesDictionary::class)->getNextStepOf($currentStepNode->step);

        if ($nextStepNode) {
            NotifyAdminsIfTraderOrderHasStopped::dispatch($traderHistory->traderOrder, $traderHistory->action)
                ->delay(now());
        }

        if ($traderHistory->traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
            return;
        }

        $stepNode = app(StepHistoriesDictionary::class)->getCompletedStepByHistory($traderHistory->action);

        $financingOrder = $traderHistory->traderOrder->order;

        if ($stepNode?->step === $this->getStepMurabhaOfferIssuedOfProvider($traderOrder->provider)) {
            app(FireWebhookWhenStatusIsMurabhaOfferIssued::class)->handle($financingOrder);

            return;
        }

        if (empty($traderHistory->traderOrder->products)) {
            return;
        }

        foreach ($this->getActionsOfProvider($traderOrder->provider, $stepNode) as $action) {
            app($action)->handle($financingOrder, $traderHistory->traderOrder);
        }
    }

    private function getActionsOfProvider($provider, $stepNode): array
    {
        return match ($provider) {
            'fake','dmcc' => match ($stepNode->step) {
                DmccMurabhaStep::CommoditySoldToCustomer => [
                    SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                    FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
                ],
                DmccMurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
                DmccMurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
                default => []
            },
            'bursam' => [
                BursamMurabhaStep::CommoditySoldToCustomer => [
                    SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                    FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
                ],
                BursamMurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
                BursamMurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
            ],
            default => []
        };
    }

    private function getStepMurabhaOfferIssuedOfProvider($provider)
    {
        return match ($provider) {
            'fake','dmcc' => [
                DmccMurabhaStep::MurabhaOfferIssued,
            ],
            'bursam' => [
                BursamMurabhaStep::MurabhaOfferIssued,
            ],
            default => []
        };
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
