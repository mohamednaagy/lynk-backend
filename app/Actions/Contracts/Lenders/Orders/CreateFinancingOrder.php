<?php

namespace App\Actions\Contracts\Lenders\Orders;

use App\Models\FinancingOrder;

interface CreateFinancingOrder
{
    public function handle(array $data): FinancingOrder;
}
