<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use Cknow\Money\Money;

class CalculateOrdersCostAction implements CalculateOrdersCost
{
    public function handle(int $ordersCount, Money $orderCost): Money
    {
        return $orderCost->multiply($ordersCount);
    }
}
