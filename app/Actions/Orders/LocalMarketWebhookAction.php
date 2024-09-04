<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Actions\Contracts\Orders\TraderOrders\completePurchasingCommodityOfTrader;
use App\Enums\TraderOrderCancelReason;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;

class LocalMarketWebhookAction implements LocalMarketWebhook
{
    public function handle(
        array $data,
    ): void {
        $traderOrder = TraderOrder::lockForUpdate()->where('reference', $data['external_order_no'])->firstOrFail();
        switch ($data['case']) {
            case 'CommoditiesPurchased':
                app(completePurchasingCommodityOfTrader::class)->handle($traderOrder, ['products' => $data['products']]);
                break;
            case 'FailedPurchase':
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::FailureToPurchase);
                break;
            case 'NoEligibleCommoditiesAvailable':
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::NoEligibleCommoditiesAvailable);
                break;

            case 'Cancelled':
                break;

            default:
                throw new \Exception('wrong format');
        }
    }
}
