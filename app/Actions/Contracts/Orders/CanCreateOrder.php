<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Company;
use Cknow\Money\Money;

interface CanCreateOrder
{
    public function handle(Company $company, Money $amount): bool;
}
