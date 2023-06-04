<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;

interface OrderBalanceDiscount
{
    public function handle(FinancingOrder $financingOrder): void;
}
