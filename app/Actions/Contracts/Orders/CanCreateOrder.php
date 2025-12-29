<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Lender;
use Cknow\Money\Money;

interface CanCreateOrder
{
    public function handle(Lender $lender, Money $amount): bool;
}
