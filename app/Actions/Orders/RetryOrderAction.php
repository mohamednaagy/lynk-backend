<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\RetryOrder;
use App\Enums\FinancingOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use App\Support\Traders\Traits\TraderHelperTrait;

class RetryOrderAction implements RetryOrder
{
    use TraderHelperTrait;

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    public function handle($orderId): void
    {
        $financingOrder = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if (! $financingOrder->status->is(FinancingOrderStatus::TradingFailure)) {
            throw new OrderStatusDoesNotFollowSequenceException(
                [
                    'financingOrderId' => $financingOrder->id,
                    'traderOrderId' => null,
                ]
            );
        }

        $this->updateOrderStatus($financingOrder, FinancingOrderStatus::PendingTraderOrder);
    }
}
