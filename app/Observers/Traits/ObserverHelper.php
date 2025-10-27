<?php

namespace App\Observers\Traits;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsMurabhaSaleCompleted;
use App\Enums\MurabhaStep;
use App\Enums\Trader;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionaryNode;

trait ObserverHelper
{
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
                    FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
                ],
                MurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
                default => []
            },
            Trader::Bursam => match ($stepNode->step) {
                MurabhaStep::CommoditySoldToCustomer => [
                    FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
                ],
                MurabhaStep::MurabahaSaleCompleted => [
                    FireWebhookWhenStatusIsMurabhaSaleCompleted::class,
                ],
                MurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
                default => []
            },
            Trader::Lynk => match ($stepNode->step) {
                MurabhaStep::CommoditySoldToCustomer => [FireWebhookWhenStatusIsCommoditySoldToCustomer::class],
                MurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
                MurabhaStep::MurabahaSaleCompleted => [FireWebhookWhenStatusIsMurabhaSaleCompleted::class],

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
