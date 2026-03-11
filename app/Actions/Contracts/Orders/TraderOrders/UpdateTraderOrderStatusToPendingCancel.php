<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Enums\TraderOrderCancelType;
use App\Models\TraderOrder;
use App\Models\User;

interface UpdateTraderOrderStatusToPendingCancel
{
    public function handle(TraderOrder $traderOrder, int $cancelReason, $cancelledByType = TraderOrderCancelType::System, ?User $cancelledBy = null);
}
