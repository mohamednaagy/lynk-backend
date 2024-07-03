<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;

class UpdateTraderOrderStatusToCancelAction implements UpdateTraderOrderStatusToCancel
{
    public function handle(TraderOrder $traderOrder, int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled, ?string $failureReason = null): void
    {
        $traderOrder->update([
            'status' => TraderOrderStatus::Cancelled,
            'cancel_reason' => $cancelReason,
            'failure_reason' => $failureReason,
            'cancelled_at' => now(),
        ]);
    }
}
