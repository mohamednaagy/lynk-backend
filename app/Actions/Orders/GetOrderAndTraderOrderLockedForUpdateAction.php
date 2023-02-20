<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLockedForUpdate;
use App\Models\TraderOrder;

class GetOrderAndTraderOrderLockedForUpdateAction implements GetOrderAndTraderOrderLockedForUpdate
{
    public function handle($traderOrderId): array
    {
        $traderOrder = TraderOrder::lockForUpdate()
            ->findOrFail($traderOrderId);

        $order = $traderOrder->order()->lockForUpdate()->first();

        $traderOrder->load([
            'traderHistories' => fn ($query) => $query->lockForUpdate(),
        ]);

        return [$order, $traderOrder];
    }
}
