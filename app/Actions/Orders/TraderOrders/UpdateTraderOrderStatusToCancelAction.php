<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Models\User;

class UpdateTraderOrderStatusToCancelAction implements UpdateTraderOrderStatusToCancel
{
    public function handle(TraderOrder $traderOrder, int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled, ?string $failureReason = null, $cancelledByType = TraderOrderCancelType::System, ?User $cancelledBy = null): void
    {
        $traderOrder->update([
            'status' => TraderOrderStatus::Cancelled,
            'cancel_reason' => $cancelReason,
            'failure_reason' => $failureReason,
            'cancelled_at' => now(),
        ]);

        $traderOrder->cancelDetail()->create([
            'cancelled_by' => $cancelledBy?->id,
            'cancel_type' => $cancelledByType,
            'cancel_step' => $traderOrder->getCancelStep(),
            'cancel_reason' => $cancelReason,
        ]);
    }
}
