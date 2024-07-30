<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Enums\FinancingOrderStatus;
use App\Exceptions\OrderAlreadyHasActiveTraderOrderException;
use App\Exceptions\OrderHasCompletedTraderOrderException;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;

class InitiateTraderOrderAction implements InitiateTraderOrder
{
    public function handle(User $user, int $orderId)
    {
        $financingOrder = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if (! $financingOrder->canCreateTraderOrder($user)) {
            if ($financingOrder->hasCompletedTraderOrder()) {
                throw new OrderHasCompletedTraderOrderException($orderId);
            }

            throw new OrderAlreadyHasActiveTraderOrderException;
        }

        //        if (! is_bursam_service_available()) {
        //            throw new CommodityMarketIsUnavailableException;
        //        }

        $driver = $financingOrder->company->getPreferredTrader();

        $trader = Trader::driver($driver, get_latest_version_of_trader($driver));
        $traderOrder = $trader->createTraderOrder($financingOrder);

        // keep below action after createTraderOrder()
        // to be sure we have a trader order and store his data in transaction meta

        $financingOrder->update([
            'status' => FinancingOrderStatus::InProgress,
        ]);
    }
}
