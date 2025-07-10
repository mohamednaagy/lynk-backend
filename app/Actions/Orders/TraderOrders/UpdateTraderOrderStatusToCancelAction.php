<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TimeLimitService;
use App\Support\Traders\Traits\TraderHelperTrait;

class UpdateTraderOrderStatusToCancelAction implements UpdateTraderOrderStatusToCancel
{
    use TraderHelperTrait;

    public function handle(TraderOrder $traderOrder, int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled, ?string $failureReason = null): void
    {

        $traderOrder->update([
            'status' => TraderOrderStatus::Cancelled,
            'failure_reason' => $failureReason,
        ]);

        $this->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::SuccessCancelled
        );

        app(TimeLimitService::class)->cancelPendingTimeLimits($traderOrder);

    }
}
