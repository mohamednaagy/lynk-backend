<?php

namespace App\Actions\Contracts\Wallets;

interface CalculateOrdersCost
{
    public function handle(int $ordersCount, $orderCost): float|int;
}
