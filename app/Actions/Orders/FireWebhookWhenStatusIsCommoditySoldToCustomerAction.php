<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsCommoditySoldToCustomerAction implements FireWebhookWhenStatusIsCommoditySoldToCustomer
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void
    {
        $sellingCommodityToCustomerMedia = $financingOrder->activeTraderOrder()
            ->first()
            ->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer);

        $url = $sellingCommodityToCustomerMedia ? $sellingCommodityToCustomerMedia->getFullUrl() : '';

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
    }
}
