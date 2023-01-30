<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;

interface UpdateTraderOrder
{
    public function handle(TraderOrder $traderOrder, array $data);
}
