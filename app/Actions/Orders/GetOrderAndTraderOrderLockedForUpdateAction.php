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

        $traderOrder->load([
            'order' => function ($query) {
                $query->when(tenant(), fn ($query) => $query->withoutGlobalScope(TenantScope::class))
                    ->lockForUpdate();
            },
            'traderHistories' => fn ($query) => $query->lockForUpdate(),
        ]);

        return [$traderOrder->order, $traderOrder];
    }
}
