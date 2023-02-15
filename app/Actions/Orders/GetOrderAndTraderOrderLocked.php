<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLocked as GetOrderAndTraderOrderLockedInterface;
use App\Models\TraderOrder;

class GetOrderAndTraderOrderLocked implements GetOrderAndTraderOrderLockedInterface
{
    public function handle($traderOrderId): array
    {
        $traderOrder = TraderOrder::lockForUpdate()
            ->findOrFail($traderOrderId);

        $order = $traderOrder->order()->lockForUpdate()->first();

        $traderHistories = $traderOrder->traderHistories()->lockForUpdate()->get();

        $traderOrder->setRelation('traderHistories', $traderHistories);

        return [$order, $traderOrder];
    }
}
