<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\TraderOrder;

interface CheckTraderOrderSettlement
{
    public function handle(TraderOrder $traderOrder): void;
}
