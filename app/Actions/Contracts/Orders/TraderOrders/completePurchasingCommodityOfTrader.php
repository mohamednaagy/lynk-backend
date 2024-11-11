<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\TraderOrder;

interface completePurchasingCommodityOfTrader
{
    public function handle(TraderOrder $traderOrder, array $data = []): TraderOrder;
}
