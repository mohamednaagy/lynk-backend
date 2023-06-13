<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;
use App\Models\User;

interface CancelTraderOrder
{
    public function handle(
        TraderOrder $traderOrder,
        User $user,
        array $data
    ): void;
}
