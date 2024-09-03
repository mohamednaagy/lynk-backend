<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Enums\TraderOrderCancelType;
use App\Models\TraderOrder;

interface UpdateTraderOrderStatusToCancel
{
    public function handle(TraderOrder $traderOrder, int $cancelReason, ?string $failureReason = null, $cancelledByType = TraderOrderCancelType::System, $cancelledBy = null);
}
