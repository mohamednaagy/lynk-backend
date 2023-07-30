<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommodityPurchased;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsCommodityPurchasedAction implements FireWebhookWhenStatusIsCommodityPurchased
{
    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        if (is_null($traderOrder->products)) {
            return;
        }

        $products = $this->resolveProducts($traderOrder);

        WebhookEvent::fire($financingOrder->company, WebhookType::OrderUpdates, [
            'order_id' => $financingOrder->id,
            'order_status' => [
                'value' => $financingOrder->status->value,
                'label' => $financingOrder->status->description,
            ],
            'products' => $products,
        ]);
    }

    public function resolveProducts(TraderOrder $traderOrder)
    {
        $products = $traderOrder->products;
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'commodity_description' => $product['product'],
                'quantity' => $product['quantity'],
            ];
        }

        return $data;
    }
}
