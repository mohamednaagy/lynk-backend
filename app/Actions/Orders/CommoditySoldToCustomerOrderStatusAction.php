<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CommoditySoldToCustomerOrderStatus;
use App\Enums\ClientMessage;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;

class CommoditySoldToCustomerOrderStatusAction implements CommoditySoldToCustomerOrderStatus
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void
    {
        if (! $financingOrder->wasChanged(['status'])) {
            return;
        }

        $sellingPrice = $financingOrder->selling_price ?? '';
        $url = $financingOrder->getMedia(FinancingOrderMediaCollection::SellingCommodityToCustomer)->first() ?? '';
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
