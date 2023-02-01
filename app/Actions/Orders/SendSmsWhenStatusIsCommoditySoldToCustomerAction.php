<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Enums\ClientMessage;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;

class SendSmsWhenStatusIsCommoditySoldToCustomerAction implements SendSmsWhenStatusIsCommoditySoldToCustomer
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void
    {
        $sellingPrice = $financingOrder->selling_price ?? '';
        $url = $financingOrder->activeTraderOrder()
            ->first()
            ->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer) ?? '';
        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $locale = app()->getLocale();

        Sms::send(
            __(ClientMessage::CommoditySoldToCustomer, [
                'product' => $product,
                'quantity' => $quantity,
                'sellingPrice' => $sellingPrice,
                'url' => $url,
            ], $locale), $phoneNumber
        );
    }
}
