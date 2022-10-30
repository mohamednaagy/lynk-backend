<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CalculateOrdersCost;

class CalculateOrdersCostAction implements CalculateOrdersCost
{
    public function handle(int $ordersCount, $orderCost): float|int
    {
        return $ordersCount * $orderCost;
    }
}
