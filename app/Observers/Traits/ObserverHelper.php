<?php

namespace App\Observers\Traits;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
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
            $timeout = app(GeneralSettings::class)->trader_order_timeout;
            NotifyAdminsIfTraderOrderHasStopped::dispatch($traderHistory->traderOrder, $traderHistory->action)
                ->delay(now()->addMinutes($timeout));
        }

        return true;
    }

    public function fireWebhookWhenStatusIsMurabhaOfferIssued(TraderOrder $traderOrder, ?StepHistoriesDictionaryNode $currentStepNode): bool
    {
        if (is_null($currentStepNode)) {
            return false;
        }

        if ($currentStepNode?->step === $this->getStepMurabhaOfferIssuedOfProvider($traderOrder->provider)) {
            app(FireWebhookWhenStatusIsMurabhaOfferIssued::class)
                ->handle($traderOrder->order);
        }

        return true;
    }

    private function getStepMurabhaOfferIssuedOfProvider($provider): string
    {
        return match ($provider) {
            'fake', 'dmcc' => DmccMurabhaStep::MurabhaOfferIssued,
            'bursam' => BursamMurabhaStep::MurabhaOfferIssued,
            default => ''
        };
    }

    private function getActionsOfProvider($provider, ?StepHistoriesDictionaryNode $stepNode): array
    {
        if (is_null($stepNode)) {
            return [];
        }

        return match ($provider) {
            'fake', 'dmcc' => match ($stepNode->step) {
                DmccMurabhaStep::CommoditySoldToCustomer => [
                    SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                    FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
                ],
                DmccMurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
                DmccMurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
                default => []
            },
            'bursam' => match ($stepNode->step) {
                BursamMurabhaStep::CommoditySoldToCustomer => [
                    SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                    FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
                ],
                BursamMurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
                BursamMurabhaStep::PurchasingCommodity => [FireWebhookWhenStatusIsCommodityPurchased::class],
                default => []
            },
            default => []
        };
    }
}
