<?php

namespace App\Support\FinancingOrders;

use App\Enums\TraderOrderRefundReason;
use App\Enums\TraderOrderStatus;
use App\Listeners\RefundOrderCost;
use App\Models\TraderOrder;

trait TraderOrderHelper
{
    private function findBaseTraderOrder(TraderOrder $traderOrder): ?TraderOrder
    {
        return $this->traderOrders
            ->where('id', '<', $traderOrder->id)
            ->where('is_base', true)
            ->first();
    }

    private function shouldSkipRefundReason(TraderOrder $traderOrder): bool
    {
        return $traderOrder->status->isNot(TraderOrderStatus::Cancelled);
    }

    private function getRefundReason(TraderOrder $traderOrder, ?TraderOrder $baseTraderOrder): string
    {
        $secondsSinceCreation = $traderOrder->created_at->diffInSeconds($baseTraderOrder->created_at);

        return TraderOrderRefundReason::fromValue(match (true) {
            ! $traderOrder->refund_reason && $secondsSinceCreation > RefundOrderCost::ONE_DAY => TraderOrderRefundReason::NO_REFUNDED_AFTER_24_HOUR,
            ! $traderOrder->refund_reason && $secondsSinceCreation > RefundOrderCost::THREE_DAYS => TraderOrderRefundReason::NO_REFUNDED_AFTER_72_HOUR,
            default => $traderOrder->refund_reason,
        })->description;
    }

    private function formatRefundReason(string $refundReason, ?TraderOrder $baseTraderOrder): string
    {
        return str_replace(':base_tr', $baseTraderOrder->reference, $refundReason);
    }
}
