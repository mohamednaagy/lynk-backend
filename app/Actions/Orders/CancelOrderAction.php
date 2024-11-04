<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelOrder;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;

class CancelOrderAction implements CancelOrder
{
    public function handle(
        FinancingOrder $financingOrder,
        User $user,
        array $data = [],
        int $cancelReason = TraderOrderCancelReason::Manual
    ): void {
        $activeTraderOrders = $financingOrder->activeTraderOrder()->lockForUpdate()->get();
        $status_reason = $data['status_reason'] ?? null;

        if ($activeTraderOrders->count() === 0) {
            $financingOrder->update([
                'status' => FinancingOrderStatus::Cancelled,
                'status_reason' => $status_reason,
            ]);

            return;
        }

        $financingOrder->update([
            'status' => FinancingOrderStatus::PendingCancellation,
            'status_reason' => $data['status_reason'] ?? null,
        ]);

        $cancelByType = is_null($user) ? TraderOrderCancelType::System : TraderOrderCancelType::User;
        $activeTraderOrders->each(function ($traderOrder) use ($user, $cancelByType) {
            Trader::driver($traderOrder->provider, $traderOrder->version)
                ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::FinancingOrderIsCancelled, cancelledBy: $user, cancelledByType: $cancelByType);
        });
    }
}
