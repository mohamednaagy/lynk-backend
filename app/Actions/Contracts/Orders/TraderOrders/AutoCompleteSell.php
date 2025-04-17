<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\ClientAutoSellPeriod;
use App\Models\TraderOrder;

interface AutoCompleteSell
{
    public function handle(TraderOrder $traderOrder, ClientAutoSellPeriod $period): void;
}
