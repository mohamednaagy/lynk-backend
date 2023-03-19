<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;

interface CreateTraderOrder
{
    public function handle($orderId, array $data): TraderOrder;
}
