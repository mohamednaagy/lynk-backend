<?php

namespace App\Observers;

use App\Enums\ClientMessage;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;
use App\Support\Webhooks\Facades\WebhookEvent;

class FinancingOrderObserver
{
    /**
     * Handle the FinancingOrder "updated" event.
     *
     * @param  FinancingOrder  $financingOrder
     * @return void
     */
    public function updated(FinancingOrder $financingOrder): void
    {
        $product = $financingOrder->activeTraderOrder()->first()->product ?? '';
        $quantity = $financingOrder->activeTraderOrder()->first()->quantity ?? '';
        $sellingPrice = $financingOrder->getOriginal('selling_price') ?? '';
        $url = $financingOrder->getMedia(FinancingOrderMediaCollection::SellingCommodityToCustomer)->first() ?? '';
        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $locale = app()->getLocale();

        match ($financingOrder->status->value) {
            FinancingOrderStatus::CommoditySoldToCustomer => Sms::driver('msegat')->send(
                __(ClientMessage::CommoditySoldToCustomer, [
                    'product' => $product,
                    'quantity' => $quantity,
                    'sellingPrice' => $sellingPrice,
                    'url' => $url,
                ], $locale), $phoneNumber),
            FinancingOrderStatus::MurabahaSaleCompleted => Sms::driver('msegat')->send(
                __(ClientMessage::MurabahaSaleCompleted, [
                    'product' => $product,
                    'quantity' => $quantity,
                    'amount' => $sellingPrice,
                ], $locale), $phoneNumber),
            FinancingOrderStatus::CommodityPurchased => WebhookEvent::fire($financingOrder->company, WebhookType::OrderUpdates, [
                'order_id' => $financingOrder->id,
                'order_status' => [
                    'value' => $financingOrder->status->value,
                    'description' => $financingOrder->status->description,
                ],
                'commodity_description' => $product,
                'quantity' => $quantity,
            ]),
        };
    }
}
