<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\ApplyEventsWhenStatusIsCommoditySoldToCustomer;
use App\Enums\ClientMessage;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;
use App\Support\Webhooks\Facades\WebhookEvent;

class ApplyEventsWhenStatusIsCommoditySoldToCustomerAction implements ApplyEventsWhenStatusIsCommoditySoldToCustomer
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void
    {
        $sellingPrice = $financingOrder->selling_price ?? '';
        $url = $financingOrder->getMedia(FinancingOrderMediaCollection::SellingCommodityToCustomer)->first() ?? '';
        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $locale = app()->getLocale();

        WebhookEvent::fire(
            $financingOrder->company,
            WebhookType::OrderUpdates, [
                'order_id' => $financingOrder->id,
                'order_status' => [
                    'value' => $financingOrder->status->value,
                    'label' => $financingOrder->status->description,
                ],
                'certificate_url' => $url,
            ]);

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
