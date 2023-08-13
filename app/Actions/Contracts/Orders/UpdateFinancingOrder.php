<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;

interface UpdateFinancingOrder
{
    public function handle(FinancingOrder $financingOrder, array $data): FinancingOrder;
}
