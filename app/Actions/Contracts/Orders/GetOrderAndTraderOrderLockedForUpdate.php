<?php

namespace App\Actions\Contracts\Orders;

interface GetOrderAndTraderOrderLockedForUpdate
{
    public function handle($traderOrderId): array;
}
