<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CalculateOrdersCost;

class CalculateOrdersCostAction implements CalculateOrdersCost
{
    public function handle(array $data): float|int
    {
        return $data['order_cost'] * $data['orders'];
    }
}
