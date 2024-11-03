<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Traits\TraderHelperTrait;

class UpdateTraderOrderStatusToPendingCancelAction implements UpdateTraderOrderStatusToPendingCancel
{
    use TraderHelperTrait;

    public function handle(TraderOrder $traderOrder, int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled, ?string $failureReason = null, $cancelledByType = TraderOrderCancelType::System, ?User $cancelledBy = null): void
    {

        $traderOrder->update([
            'status' => TraderOrderStatus::PendingCancellation,
            'cancel_reason' => $cancelReason,
            'failure_reason' => $failureReason,
            'pending_cancelled_at' => now(),
        ]);

        $this->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::PendingCancellation
        );

        $traderOrder->cancelDetail()->create([
            'cancelled_by' => $cancelledBy?->id,
            'cancel_type' => $cancelledByType,
            'cancel_step' => $traderOrder->getCancelStep(),
            'cancel_reason' => $cancelReason,
        ]);

    }
}
