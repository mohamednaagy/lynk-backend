<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Database\Eloquent\Model;

interface CreateFinancingOrder
{
    public function handle(Company $company, array $data): FinancingOrder|Model;
}
