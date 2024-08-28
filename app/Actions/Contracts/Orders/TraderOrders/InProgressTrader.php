<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\TraderOrder;

interface InProgressTrader
{
    public function handle(TraderOrder $traderOrder, array $data = []): TraderOrder;
}
