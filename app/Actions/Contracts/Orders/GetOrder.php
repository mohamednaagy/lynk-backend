<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Company;

interface GetOrder
{
    public function handle(int $order);

    public function setCompany(Company $company);
}
