<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;
use App\Models\Lender;

interface CreateFinancingOrder
{
    public function handle(Lender $lender, array $data): FinancingOrder;
}
