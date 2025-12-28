<?php

namespace App\Services\FinancingOrder;

use App\Enums\CompanyMarketType;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderCancelReason;
use App\Models\FinancingOrder;

class FinancingOrderCancellationStatusResolver
{
    /**
     * Determine the next status for a financing order after cancellation.
     *
     * @return int|null Returns the FinancingOrderStatus value or null if no change is needed.
     */
    public function resolve(FinancingOrder $order, int $cancelReason): int
    {
        if ($order->status->is(FinancingOrderStatus::PendingCancellation)) {
            return FinancingOrderStatus::Cancelled;
        }

        if ($order->status->is(FinancingOrderStatus::InProgress)) {
            return $this->resolveForInProgressOrder($order, $cancelReason);
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Cannot cancel financing order #%d: status "%s" is not eligible for cancellation.',
                $order->id,
                $order->status->value
            )
        );
    }

    /**
     * Resolve status for InProgress orders based on lender preferences and cancellation reason.
     */
    private function resolveForInProgressOrder(FinancingOrder $order, int $cancelReason): int
    {
        if ($this->shouldFailTrading($order, $cancelReason)) {
            return FinancingOrderStatus::TradingFailure;
        }

        return FinancingOrderStatus::PendingTraderOrder;
    }

    /**
     * Check if the trading should be considered a failure.
     */
    private function shouldFailTrading(FinancingOrder $order, int $cancelReason): bool
    {
        $lender = $order->lender;

        $isLocalMarket = $lender->lenderDetail?->preferred_market_type?->is(CompanyMarketType::Local());
        $isFailureReason = in_array($cancelReason, [
            TraderOrderCancelReason::FailureToPurchase,
            TraderOrderCancelReason::FailureToSellAtLocalMarket,
        ]);

        return $isLocalMarket && $isFailureReason;
    }
}
