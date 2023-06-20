<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;

class CancelOrderAction implements CancelOrder
{
    public function handle(
        FinancingOrder $financingOrder,
        User $user,
        array $data,
        int $cancelReason
    ): void {
        $activeTraderOrders = $financingOrder->activeTraderOrder()->lockForUpdate()->get();

        if ($activeTraderOrders->count() === 0) {
            $financingOrder->update([
                'status' => FinancingOrderStatus::Cancelled,
                'status_reason' => $data['status_reason'] ?? null,
            ]);

            return;
        }

        $financingOrder->update([
            'status' => FinancingOrderStatus::PendingCancellation,
            'status_reason' => $data['status_reason'] ?? null,
        ]);

        $activeTraderOrders->each(function ($traderOrder) use ($cancelReason) {
            Trader::driver($traderOrder->provider, $traderOrder->version)
                ->cancelTraderOrder($traderOrder, $cancelReason);
        });
    }
}
