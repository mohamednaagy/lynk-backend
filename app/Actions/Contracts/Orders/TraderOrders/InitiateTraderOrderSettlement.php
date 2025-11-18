<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\TraderOrder;
use App\Models\TraderOrderSettlement;

interface InitiateTraderOrderSettlement
{
    public function handle(TraderOrder $traderOrder): ?TraderOrderSettlement;
}
