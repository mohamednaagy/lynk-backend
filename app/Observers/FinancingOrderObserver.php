<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Models\FinancingOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;

class FinancingOrderObserver
{
    //    /**
    //     * Handle the FinancingOrder "updated" event.
    //     *
    //     * @param  FinancingOrder  $financingOrder
    //     * @return void
    //     *
    //     * @throws \Exception
    //     */
    //    public function updated(FinancingOrder $financingOrder): void
    //    {
    //        if (! $financingOrder->wasChanged(['status'])) {
    //            return;
    //        }
    //
    //        $traderOrder = $financingOrder->activeTraderOrder()
    //            ->withLastHistoryAction()
    //            ->first();
    //
    //        $stepNode = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
    //            ->getStepByHistory($traderOrder->last_history_action);
    //
    //        if ($traderOrder->provider == 'dmcc') {
    //            if ($stepNode->step == DmccMurabhaStep::MurabhaOfferIssued) {
    //                app(FireWebhookWhenStatusIsMurabhaOfferIssued::class)->handle($financingOrder);
    //
    //                return;
    //            }
    //        }
    //
    //        if (empty($traderOrder->products)) {
    //            return;
    //        }
    //
    //        foreach ($this->getActionsOfProvider($traderOrder->provider, $stepNode) as $action) {
    //            app($action)->handle($financingOrder, $traderOrder);
    //        }
    //    }
    //
    //    private function getActionsOfProvider($provider, $stepNode): array
    //    {
    //        return match ($provider) {
    //            'dmcc' => match ($stepNode->step) {
    //                DmccMurabhaStep::CommoditySoldToCustomer => [
    //                    SendSmsWhenStatusIsCommoditySoldToCustomer::class,
    //                    FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
    //                ],
    //                DmccMurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
    //                DmccMurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
    //                default => []
    //            },
    //            'bursam' => [
    //                BursamMurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
    //            ],
    //            default => []
    //        };
    //    }
}
