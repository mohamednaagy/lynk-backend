<?php

namespace App\Actions\Contracts\Lender;

use App\Models\FinancingOrder;

interface CreateFinancingOrder
{
    public function handle(array $data): FinancingOrder;
}
