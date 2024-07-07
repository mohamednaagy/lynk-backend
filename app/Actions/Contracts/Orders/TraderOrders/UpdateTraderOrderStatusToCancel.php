<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\TraderOrder;
use App\Models\User;

interface UpdateTraderOrderStatusToCancel
{
    public function handle(TraderOrder $traderOrder, int $cancelReason, ?string $failureReason = null, ?User $user = null);
}
