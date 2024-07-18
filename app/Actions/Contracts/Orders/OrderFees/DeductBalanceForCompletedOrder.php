<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;

interface DeductBalanceForCompletedOrder
{
    public function handle(TraderOrder $traderOrder): void;
}
