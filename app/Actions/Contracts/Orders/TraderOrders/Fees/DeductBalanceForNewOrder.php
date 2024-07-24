<?php

namespace App\Actions\Contracts\Orders\TraderOrders\Fees;

use App\Models\TraderOrder;

interface DeductBalanceForNewOrder
{
    public function handle(TraderOrder $traderOrder): void;
}
