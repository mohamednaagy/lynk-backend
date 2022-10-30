<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Company;

interface GetOrdersStats
{
    public function handle(Company $company);
}
