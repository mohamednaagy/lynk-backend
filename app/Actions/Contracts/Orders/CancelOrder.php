<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;

interface CancelOrder
{
    public function handle(
        FinancingOrder $financingOrder,
        TraderOrder $traderOrder,
        User $user,
        array $data
    ): void;
}
