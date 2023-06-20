<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Exceptions\OrderAlreadyHasActiveTraderOrderException;
use App\Exceptions\StockMarketIsUnavailableException;
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

        $driver = config('trader.default');
        $trader = Trader::driver($driver, get_latest_version_of_trader($driver));

        if ($driver == 'bursam' && ! isBursamServiceAvailable()) {
            throw new StockMarketIsUnavailableException;
        }

        $trader->createTraderOrder($financingOrder);
    }
}
