<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\InProgressTrader;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;

class InProgressTraderAction implements InProgressTrader
{
    public function handle(TraderOrder $traderOrder, array $data = []): TraderOrder
    {
        $traderOrder->update(['status' => TraderOrderStatus::InProgress, $data]);

        return $traderOrder;
    }
}
