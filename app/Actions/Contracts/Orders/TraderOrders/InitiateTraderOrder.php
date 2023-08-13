<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

interface InitiateTraderOrder
{
    public function handle(int $orderId);
}
