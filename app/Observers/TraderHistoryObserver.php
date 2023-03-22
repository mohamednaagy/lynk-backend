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
use App\Models\FinancingOrder;
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
     *
     * @throws \Exception
     */
    public function created(TraderHistory $traderHistory)
    {
        /** @var FinancingOrder $financingOrder */
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

        if ($traderHistory->traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
            return;
        }

        $stepNode = app(StepHistoriesDictionary::class)->getStepByHistory($traderHistory->action);

        if ($stepNode->step === MurabhaStep::MurabhaOfferIssued) {
            app(FireWebhookWhenStatusIsMurabhaOfferIssued::class)->handle($financingOrder);

            return;
        }

        if (empty($traderOrder->products)) {
            return;
        }

        $actions = match ($stepNode->step) {
            MurabhaStep::CommoditySoldToCustomer => [
                SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
            ],
            MurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
            MurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
            default => []
        };

        foreach ($actions as $action) {
            app($action)->handle($financingOrder, $traderOrder);
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
