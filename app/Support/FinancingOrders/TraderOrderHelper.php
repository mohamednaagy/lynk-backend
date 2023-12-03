<?php

namespace App\Support\FinancingOrders;

use App\Enums\TraderOrderNoRefundReason;
use App\Enums\TraderOrderRefundReason;
use App\Enums\TraderOrderStatus;
use App\Listeners\RefundOrderCost;
use App\Models\TraderOrder;
use Carbon\Carbon;

trait TraderOrderHelper
{
    private function findBaseTraderOrder(TraderOrder $traderOrder): ?TraderOrder
    {
        return $this->currentOrderTraderOrders
            ->where('id', '<=', $traderOrder->id)
            ->where('is_base', true)
            ->last();
    }

    private function shouldSkipRefundStatus(TraderOrder $traderOrder): bool
    {
        return $traderOrder->status->isNot(TraderOrderStatus::Cancelled);
    }

    private function getRefundStatus(TraderOrder $traderOrder, TraderOrder $baseTraderOrder): ?string
    {
        $canceledAt = $traderOrder->cancelled_at
            ? Carbon::parse($traderOrder->cancelled_at)
            : null;

        $secondsSinceCancellation = $canceledAt?->diffInSeconds($baseTraderOrder->created_at);

        $refundStatus = match (true) {
            ! $traderOrder->refund_reason && $secondsSinceCancellation > RefundOrderCost::ONE_DAY => TraderOrderNoRefundReason::AFTER_24_HOUR(),
            ! $traderOrder->refund_reason && $secondsSinceCancellation > RefundOrderCost::THREE_DAYS => TraderOrderNoRefundReason::AFTER_72_HOUR(),
            is_null($traderOrder->refund_reason) => null,
            default => TraderOrderRefundReason::fromValue($traderOrder->refund_reason),
        };

        return $refundStatus?->description;
    }

    private function formatRefundStatus(string $refundReason, TraderOrder $baseTraderOrder): string
    {
        return str_replace(':base_tr', $baseTraderOrder->reference, $refundReason);
    }
}
