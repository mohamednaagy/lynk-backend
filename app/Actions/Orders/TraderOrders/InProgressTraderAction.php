<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\InProgressTrader;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
use Illuminate\Http\Request;

class InProgressTraderAction implements InProgressTrader
{
    public function handle(TraderOrder $traderOrder, array $data = []): TraderOrder
    {
        $traderOrder->update(['status' => TraderOrderStatus::InProgress, $data]);
        $request = Request::create('/', 'POST', $data);
        (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))->updatePurchasingCommodity($traderOrder, $request);

        return $traderOrder;
    }
}
