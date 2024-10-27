<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\RetryOrder;
use App\Enums\FinancingOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;

class RetryOrderAction implements RetryOrder
{
    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    public function handle($orderId): void
    {
        $financingOrder = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if (! $financingOrder->status->is(FinancingOrderStatus::TradingFailure)) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $activeTraderOrder = $financingOrder->traderOrders()->latest()->first();
        Trader::driver($activeTraderOrder->provider, $activeTraderOrder->version)
            ->retryOrder($activeTraderOrder);
    }
}
