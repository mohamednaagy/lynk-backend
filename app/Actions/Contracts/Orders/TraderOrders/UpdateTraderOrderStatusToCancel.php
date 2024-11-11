<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Enums\TraderOrderCancelReason;
use App\Models\TraderOrder;

interface UpdateTraderOrderStatusToCancel
{
    public function handle(TraderOrder $traderOrder, int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled, ?string $failureReason = null);
}
