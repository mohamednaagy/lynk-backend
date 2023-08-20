<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\DeductBalanceForNewOrder;
use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Enums\FinancingOrderStatus;
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

        if (! $financingOrder->canCreateTraderOrder(request()->user())) {
            throw new OrderAlreadyHasActiveTraderOrderException;
        }

        if (! $financingOrder->isBursamTraderServiceAvailable()) {
            throw new CommodityMarketIsUnavailableException;
        }

        $driver = config('trader.default');
        $trader = Trader::driver($driver, get_latest_version_of_trader($driver));
        $traderOrder = $trader->createTraderOrder($financingOrder);

        // keep below action after createTraderOrder()
        // to be sure we have a trader order and store his data in transaction meta
        app(DeductBalanceForNewOrder::class)->handle($traderOrder);

        $financingOrder->update([
            'status' => FinancingOrderStatus::InProgress,
        ]);
    }
}
