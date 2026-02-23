<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelOrder;
use App\Enums\FinancingOrderCancelReason;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;

class CancelOrderAction implements CancelOrder
{
    use TraderHelperTrait;

    public function handle(
        FinancingOrder $financingOrder,
        User $user,
        array $data = [],
        int $cancelReason = TraderOrderCancelReason::Manual
    ): void {
        $activeTraderOrders = $financingOrder->activeTraderOrder()->get();
        $statusReason = $data['status_reason'] ?? null;
        if ($activeTraderOrders->count() === 0) {
            $this->updateOrderStatus($financingOrder, FinancingOrderStatus::Cancelled);
            if ($statusReason) {
                $financingOrder->cancelDetail()->create([
                    'creator_id' => $user->id,
                    'cancel_reason' => FinancingOrderCancelReason::Cancelled,
                    'comment' => $statusReason,
                ]);
            }

            return;
        }

        $this->updateOrderStatus($financingOrder, FinancingOrderStatus::PendingCancellation);
        $financingOrder->cancelDetail()->create([
            'creator_id' => $user->id,
            'cancel_reason' => FinancingOrderCancelReason::Cancelled,
            'comment' => $statusReason,
        ]);
        $cancelByType = is_null($user) ? TraderOrderCancelType::System : TraderOrderCancelType::User;
        $activeTraderOrders->each(function ($traderOrder) use ($user, $cancelByType) {
            Trader::driver($traderOrder->provider, $traderOrder->version)
                ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::FinancingOrderIsCancelled, cancelledBy: $user, cancelledByType: $cancelByType);
        });
    }
}
