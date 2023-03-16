<?php

namespace App\Actions\Contracts\Orders;

interface CreateTrading
{
    public function handle($orderId, array $data): void;
}
