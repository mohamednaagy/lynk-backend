<?php

namespace App\Actions\Contracts\Orders\TraderOrders\Fees;

use App\Models\TraderOrder;

interface DeductBalanceForCompletedOrder
{
    public function handle(TraderOrder $traderOrder): void;
}
