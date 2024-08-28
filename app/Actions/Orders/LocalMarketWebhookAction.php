<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;

class LocalMarketWebhookAction implements LocalMarketWebhook
{
    public function handle(
        TraderOrder $traderOrder,
        array $data,
    ): void {
        switch ($data['case']) {
            case 'CommoditiesPurchased':
                $traderOrder->update(['status' => TraderOrderStatus::InProgress, 'products' => $data['products']]);
                break;

            case 'FailedPurchase':
                $traderOrder->update(['status' => TraderOrderStatus::Cancelled, 'products' => $data['products']]);
                break;

            case 'Cancelled':
                break;

            default:
                throw new \Exception('wrong format');
        }
    }
}
