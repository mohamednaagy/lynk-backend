<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CommodityPurchasedOrderStatus;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class CommodityPurchasedOrderStatusAction implements CommodityPurchasedOrderStatus
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void
    {
        if (! $financingOrder->wasChanged(['status'])) {
            return;
        }

        WebhookEvent::fire($financingOrder->company, WebhookType::OrderUpdates, [
            'order_id' => $financingOrder->id,
            'order_status' => [
                'value' => $financingOrder->status->value,
                'label' => $financingOrder->status->description,
            ],
            'commodity_description' => $product,
            'quantity' => $quantity,
        ]);
    }
}
