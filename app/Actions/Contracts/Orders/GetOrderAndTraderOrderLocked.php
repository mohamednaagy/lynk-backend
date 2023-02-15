<?php

namespace App\Actions\Contracts\Orders;

interface GetOrderAndTraderOrderLocked
{
    public function handle($traderOrderId): array;
}
