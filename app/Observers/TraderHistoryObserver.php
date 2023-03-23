<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Jobs\FinancingOrders\NotifyAdminsIfTraderOrderHasStopped;
use App\Models\TraderHistory;
use App\Settings\Classes\GeneralSettings;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;

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
    public function created(TraderHistory $traderHistory)
    {
        $timeout = app(GeneralSettings::class)->trader_order_timeout;

        // some Order at last step so no next step I think  another mail content needed
        $currentStepNode = app(StepHistoriesDictionary::class)->getStepByHistory($traderHistory->action);
        $nextStepNode = app(StepHistoriesDictionary::class)->getNextStepOf($currentStepNode->step);

        if ($nextStepNode) {
            NotifyAdminsIfTraderOrderHasStopped::dispatch($traderHistory->traderOrder, $traderHistory->action)
                ->delay(now()->addMinutes($timeout));
        }

        if ($traderHistory->traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
            return;
        }

        $stepNode = app(StepHistoriesDictionary::class)->getCompletedStepByHistory($traderHistory->action);

        $financingOrder = $traderHistory->traderOrder->order;

        if ($stepNode?->step === MurabhaStep::MurabhaOfferIssued) {
            app(FireWebhookWhenStatusIsMurabhaOfferIssued::class)->handle($financingOrder);

            return;
        }

        if (empty($traderHistory->traderOrder->products)) {
            return;
        }

        $actions = match ($stepNode?->step) {
            MurabhaStep::CommoditySoldToCustomer => [
                SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
            ],
            MurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
            MurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
            default => []
        };

        foreach ($actions as $action) {
            app($action)->handle($financingOrder, $traderHistory->traderOrder);
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
