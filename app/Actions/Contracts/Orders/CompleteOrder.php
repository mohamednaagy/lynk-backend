<?php

namespace App\Actions\Contracts\Orders;

interface CompleteOrder
{
    public function handle($orderId, array $data);
}
