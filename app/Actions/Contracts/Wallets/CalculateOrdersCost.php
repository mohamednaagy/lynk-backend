<?php

namespace App\Actions\Contracts\Wallets;

use Cknow\Money\Money;

interface CalculateOrdersCost
{
    public function handle(Money $orderCostWithoutVat): Money;
}
