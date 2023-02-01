<?php

namespace App\Actions\Contracts\Admins\Lenders\Orders;

use App\Models\FinancingOrder;

interface ProceedSellingCommodity
{
    public function handle(FinancingOrder $financingOrder);
}
