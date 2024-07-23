<?php

namespace App\Observers\Traits;

use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsMurabhaSaleCompleted;
use App\Enums\MurabhaStep;
use App\Enums\Trader;
use App\Jobs\FinancingOrders\NotifyAdminsIfTraderOrderHasStopped;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Settings\Classes\GeneralSettings;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionaryNode;

trait ObserverHelper
{
    public function notifyAdminsAboutOrderStopped(TraderHistory $traderHistory, ?StepHistoriesDictionaryNode $currentStepNode): bool
    {
        if (is_null($currentStepNode)) {
            return false;
        }

        $nextStepNode = app(StepHistoriesDictionary::class)->getNextStepOf($currentStepNode->step);

        if ($nextStepNode) {
            $delayTime = app(GeneralSettings::class)->trader_order_timeout;
            if ($this->isPurchasingOrSellingCommodity($nextStepNode->step)) {
                $delayTime = 1;
            }

            NotifyAdminsIfTraderOrderHasStopped::dispatch($traderHistory->traderOrder, $traderHistory->action)
                ->delay(now()->addMinutes($delayTime));
        }

        return true;
    }

    public function fireWebhookWhenStatusIsMurabhaOfferIssued(TraderOrder $traderOrder, ?StepHistoriesDictionaryNode $currentStepNode): bool
    {
        if (is_null($currentStepNode)) {
            return false;
        }

        if ($currentStepNode?->step === MurabhaStep::MurabhaOfferIssued) {
            app(FireWebhookWhenStatusIsMurabhaOfferIssued::class)
                ->handle($traderOrder->order);
        }

        return true;
    }

    private function getActionsOfProvider($provider, ?StepHistoriesDictionaryNode $stepNode): array
    {
        if (is_null($stepNode)) {
            return [];
        }

        return match ($provider) {
            Trader::FakeDmcc, Trader::Dmcc => match ($stepNode->step) {
                MurabhaStep::CommoditySoldToCustomer => [
                    SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                    FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
                ],
                MurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
                MurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
                default => []
            },
            Trader::Bursam => match ($stepNode->step) {
                MurabhaStep::CommoditySoldToCustomer => [
                    SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                    FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
                ],
                MurabhaStep::MurabahaSaleCompleted => [
                    SendSmsWhenStatusIsMurabahaSaleCompleted::class,
                    FireWebhookWhenStatusIsMurabhaSaleCompleted::class,
                ],
                MurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
                default => []
            },
            Trader::Lynk => match ($stepNode->step) {
                MurabhaStep::MurabahaSaleCompleted => [FireWebhookWhenStatusIsMurabhaSaleCompleted::class],
                MurabhaStep::CommoditySoldToCustomer => [FireWebhookWhenStatusIsCommoditySoldToCustomer::class],
                MurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],

                default => []
            },
            default => []
        };
    }

    private function isPurchasingOrSellingCommodity($step): bool
    {
        return in_array($step, [
            MurabhaStep::PurchasingCommodity,
            MurabhaStep::MurabahaSaleCompleted,
        ]);
    }
}
