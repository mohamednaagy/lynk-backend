<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\RetryOrder;
use App\Enums\FinancingOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;

class RetryOrderAction implements RetryOrder
{
    /**
     * @param $orderId
     * @return void
     *
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

        $financingOrder->update(['status' => FinancingOrderStatus::Approved]);
    }
}
