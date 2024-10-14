<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\TraderOrderCancelReason;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
use Illuminate\Support\Facades\Log;

class LocalMarketWebhookAction implements LocalMarketWebhook
{
    public function handle(
        array $data,
    ): void {

        $traderOrder = TraderOrder::lockForUpdate()->where('reference', $data['external_order_no'])->firstOrFail();
        Log::channel('local_market')->info("update trader by local market webhook Trader Order id {$traderOrder->id} with case =>".$data['case']);
        switch ($data['case']) {
            case 'CommoditiesPurchased':
                $data['auto_generate_financing_institution_certificate'] = 1;
                (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))->updatePurchasingCommodity($traderOrder, $data);
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
