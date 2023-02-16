<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;

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

        $product = $financingOrder->activeTraderOrder()->first()->product ?? '';
        $quantity = $financingOrder->activeTraderOrder()->first()->quantity ?? '';

        $actions = match ($financingOrder->status->value) {
            FinancingOrderStatus::CommoditySoldToCustomer => [
                SendSmsWhenStatusIsCommoditySoldToCustomer::class,
                FireWebhookWhenStatusIsCommoditySoldToCustomer::class,
            ],
            FinancingOrderStatus::MurabahaSaleCompleted => [SendSmsWhenStatusIsMurabahaSaleCompleted::class],
            FinancingOrderStatus::CommodityPurchased => [FireWebhookWhenStatusIsCommodityPurchased::class],
            default => []
        };

        foreach ($actions as $action) {
            app($action)->handle($financingOrder, $product, $quantity);
        }

        if ($financingOrder->status->is(FinancingOrderStatus::MurabhaOfferIssued)) {
            app(FireWebhookWhenStatusIsMurabhaOfferIssued::class)->handle($financingOrder);
        }
    }
}
