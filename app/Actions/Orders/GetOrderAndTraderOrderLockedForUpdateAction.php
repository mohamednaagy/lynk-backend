<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLockedForUpdate;
use App\Models\TraderOrder;
use Stancl\Tenancy\Database\TenantScope;

class GetOrderAndTraderOrderLockedForUpdateAction implements GetOrderAndTraderOrderLockedForUpdate
{
    public function handle($traderOrderId): array
    {
        $traderOrder = TraderOrder::lockForUpdate()
            ->findOrFail($traderOrderId);

        $order = $traderOrder->order();

        if (tenant()) {
            $order->withoutGlobalScope(TenantScope::class);
        }

        $traderOrder->load([
            'traderHistories' => fn ($query) => $query->lockForUpdate(),
        ]);

        return [$order->lockForUpdate()->first(), $traderOrder];
    }
}
