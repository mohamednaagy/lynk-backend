<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;

interface DepositBalance
{
    public function handle(FinancingOrder $financingOrder);
}
