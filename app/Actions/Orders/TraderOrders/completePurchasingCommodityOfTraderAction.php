<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\completePurchasingCommodityOfTrader;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
use Illuminate\Http\Request;

class completePurchasingCommodityOfTraderAction implements completePurchasingCommodityOfTrader
{
    public function handle(TraderOrder $traderOrder, array $data = []): TraderOrder
    {
        $traderOrder->update(['status' => TraderOrderStatus::InProgress, 'products' => $data['products']]);
        $data['auto_generate_financing_institution_certificate'] = 1;
        $request = Request::create('/', 'POST', $data);
        (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))->updatePurchasingCommodity($traderOrder, $request);

        return $traderOrder;
    }
}
