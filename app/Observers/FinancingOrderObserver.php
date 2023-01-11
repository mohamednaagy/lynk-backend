<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\CommodityPurchasedOrderStatus;
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
     *
     * @throws \Exception
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
            FinancingOrderStatus::CommodityPurchased => app(CommodityPurchasedOrderStatus::class)->handle($financingOrder, $product, $quantity),
            default => null
        };
    }
}
