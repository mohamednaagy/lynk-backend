<?php

namespace App\Actions\Contracts\Orders;

interface GetOrder
{
    public function handle(int $order);
}
