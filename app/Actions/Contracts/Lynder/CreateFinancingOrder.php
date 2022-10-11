<?php

namespace App\Actions\Contracts\Lynder;

use App\Models\FinancingOrder;

interface CreateFinancingOrder
{
    public function handle(array $data): FinancingOrder;
}
