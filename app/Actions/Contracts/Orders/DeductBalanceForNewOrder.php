<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;

interface DeductBalanceForNewOrder
{
    public function handle(FinancingOrder $financingOrder): void;
}
