<?php

namespace App\Actions\Orders;

use App\Models\FinancingOrder;
use App\Models\Lender;

interface FinancingOrderTypeStrategy
{
    public function create(Lender $lender, array $data): FinancingOrder;
}
