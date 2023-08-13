<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;

interface DeductBalanceForNewOrder
{
    public function handle(TraderOrder $traderOrder): void;
}
