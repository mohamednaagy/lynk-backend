<?php

namespace App\Actions\Orders;

use App\Models\Company;
use App\Models\FinancingOrder;

interface FinancingOrderTypeStrategy
{
    public function create(Company $company, array $data): FinancingOrder;
}
