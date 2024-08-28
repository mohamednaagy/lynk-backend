<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelTraderOrder;
use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Actions\Contracts\Orders\TraderOrders\InProgressTrader;
use App\Enums\TraderOrderCancelReason;
use App\Models\TraderOrder;

class LocalMarketWebhookAction implements LocalMarketWebhook
{
    public function handle(
        array $data,
    ): void {
        $traderOrder = TraderOrder::lockForUpdate()->where('reference', $data['external_order_no'])->firstOrFail();
        switch ($data['case']) {
            case 'CommoditiesPurchased':
                app(InProgressTrader::class)->handle($traderOrder, ['products' => $data['products']]);
                break;
            case 'FailedPurchase':
                app(CancelTraderOrder::class)->handle($traderOrder, [], TraderOrderCancelReason::Manual);
                break;

            case 'Cancelled':
                break;

            default:
                throw new \Exception('wrong format');
        }
    }
}
