<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Enums\MurabhaStep;
use App\Models\FinancingOrder;

// :TODO find a handle for this observer
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

        if ($financingOrder->status->is(MurabhaStep::MurabhaOfferIssued)) {
            app(FireWebhookWhenStatusIsMurabhaOfferIssued::class)->handle($financingOrder);

            return;
        }

        $traderOrder = $financingOrder->activeTraderOrder()->first();

        if (empty($traderOrder->products)) {
            return;
        }

        $actions = match ($financingOrder->status->value) {
            MurabhaStep::CommoditySoldToCustomer => [
                SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
            ],
            MurabhaStep::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
            MurabhaStep::CommodityPurchased => [FireWebhookWhenStatusIsCommodityPurchased::class],
            default => []
        };

        foreach ($actions as $action) {
            app($action)->handle($financingOrder, $traderOrder);
        }
    }
}
