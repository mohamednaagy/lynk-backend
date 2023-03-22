<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Enums\MurabhaStep;
use App\Models\FinancingOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;

class FinancingOrderObserver
{
    /**
     * Handle the FinancingOrder "updated" event.
     *
     * @param  FinancingOrder  $financingOrder
     * @return void
     *
     * @throws \Exception
     */
    public function updated(FinancingOrder $financingOrder): void
    {
        if (! $financingOrder->wasChanged(['status'])) {
            return;
        }

//        $traderOrder = $financingOrder->activeTraderOrder()
//            ->withLastHistoryAction()
//            ->first();
//
//        $stepNode = app(StepHistoriesDictionary::class)->getStepByHistory($traderOrder->last_history_action);
//
//        if ($stepNode->step == MurabhaStep::MurabhaOfferIssued) {
//            app(FireWebhookWhenStatusIsMurabhaOfferIssued::class)->handle($financingOrder);
//
//            return;
//        }
//
//        if (empty($traderOrder->products)) {
//            return;
//        }
//
//        $actions = match ($stepNode->step) {
//            MurabhaStep::CommoditySoldToCustomer => [
//                SendSmsWhenStatusIsCommoditySoldToCustomer::class,
//                FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
//            ],
//            MurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
//            MurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
//            default => []
//        };
//
//        foreach ($actions as $action) {
//            app($action)->handle($financingOrder, $traderOrder);
//        }
    }
}
