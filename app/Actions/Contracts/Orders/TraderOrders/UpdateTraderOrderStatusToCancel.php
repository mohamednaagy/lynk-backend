<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Enums\TraderOrderCancelType;
use App\Models\TraderOrder;
use App\Models\User;

interface UpdateTraderOrderStatusToCancel
{
    public function handle(TraderOrder $traderOrder, int $cancelReason, ?string $failureReason = null, $cancelledByType = TraderOrderCancelType::System, ?User $cancelledBy = null);
}
