<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;

class CancelOrderAction implements CancelOrder
{
    public function handle(FinancingOrder $financingOrder, User $user, array $data): void
    {
        $traderOrder = $financingOrder->activeTraderOrder()->first();

        if ($traderOrder) {
            Trader::driver($traderOrder->provider, $traderOrder->version)
                ->cancelOrder($financingOrder);
        }

        $financingOrder->update([
            'status' => FinancingOrderStatus::PendingCancellation,
            'status_reason' => $data['status_reason'] ?? null,
        ]);
    }
}
