<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Exceptions\CommodityMarketIsUnavailableException;
use App\Exceptions\OrderAlreadyHasActiveTraderOrderException;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;

class InitiateTraderOrderAction implements InitiateTraderOrder
{
    public function handle(int $orderId)
    {
        $financingOrder = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if (! $financingOrder->canCreateTraderOrder()) {
            throw new OrderAlreadyHasActiveTraderOrderException;
        }

        if (! $financingOrder->isBursamTraderServiceAvailable()) {
            throw new CommodityMarketIsUnavailableException;
        }

        $driver = config('trader.default');
        $trader = Trader::driver($driver, get_latest_version_of_trader($driver));
        $trader->createTraderOrder($financingOrder);
    }
}
