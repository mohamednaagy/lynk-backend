<?php

namespace App\Actions\Contracts\Orders;

use App\Enums\TraderOrderCancelReason;
use App\Models\FinancingOrder;
use App\Models\User;

interface CancelOrder
{
    public function handle(
        FinancingOrder $financingOrder,
        User $user,
        array $data = [],
        int $cancelReason = TraderOrderCancelReason::Manual
    ): void;
}
