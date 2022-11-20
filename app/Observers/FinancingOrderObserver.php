<?php

namespace App\Observers;

use App\Enums\ClientMessage;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;

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
        match ($financingOrder->status->value) {
            FinancingOrderStatus::CommoditySoldToCustomer => Sms::driver('msegat')->send(
                __(ClientMessage::CommoditySoldToCustomer, [
                    'product' => $financingOrder->activeTraderOrder()->first()->data->product,
                    'quantity' => $financingOrder->activeTraderOrder()->first()->data->quantity,
                    'sellingPrice' => $financingOrder->getOriginal('selling_price'),
                    'url' => $financingOrder->getMedia(FinancingOrderMediaCollection::SellingCommodityToCustomer)->first(),
                ], app()->getLocale()), ltrim($financingOrder->getPhoneNumber()->formatE164(), '+')),
            FinancingOrderStatus::MurabahaSaleCompleted => Sms::driver('msegat')->send(
                __(ClientMessage::MurabahaSaleCompleted, [
                    'product' => $financingOrder->activeTraderOrder()->first()->data->product,
                    'quantity' => $financingOrder->activeTraderOrder()->first()->data->quantity,
                    'amount' => $financingOrder->getOriginal('selling_price'),
                ], app()->getLocale()), ltrim($financingOrder->getPhoneNumber()->formatE164(), '+')),
            default => new \ErrorException('Error found'),
        };
    }
}
