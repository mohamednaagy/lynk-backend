<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Company;

interface CanCreateOrder
{
    public function handle(Company $company): bool;
}
