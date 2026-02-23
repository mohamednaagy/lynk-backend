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
                $this->createCancelDetail($financingOrder, $user, $statusReason, FinancingOrderCancelReason::Cancelled);
            }

            return;
        }

        $this->updateOrderStatus($financingOrder, FinancingOrderStatus::PendingCancellation);
        if ($statusReason) {
            $this->createCancelDetail($financingOrder, $user, $statusReason, FinancingOrderCancelReason::Cancelled);
        }
        $cancelByType = is_null($user) ? TraderOrderCancelType::System : TraderOrderCancelType::User;
        $activeTraderOrders->each(function ($traderOrder) use ($user, $cancelByType) {
            Trader::driver($traderOrder->provider, $traderOrder->version)
                ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::FinancingOrderIsCancelled, cancelledBy: $user, cancelledByType: $cancelByType);
        });
    }

    private function createCancelDetail(FinancingOrder $financingOrder, User $user, string $comment, int $cancelReason): void
    {
        $financingOrder->cancelDetail()->create([
            'creator_id' => $user->id,
            'cancel_reason' => $cancelReason,
            'comment' => $comment,
        ]);
    }
}
