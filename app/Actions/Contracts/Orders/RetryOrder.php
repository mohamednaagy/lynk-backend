<?php

namespace App\Actions\Contracts\Orders;

interface RetryOrder
{
    public function handle($orderId);
}
