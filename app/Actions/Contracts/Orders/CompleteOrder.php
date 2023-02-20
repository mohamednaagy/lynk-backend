<?php

namespace App\Actions\Contracts\Orders;

interface CompleteOrder
{
    public function handle($traderOrderId, array $data);
}
