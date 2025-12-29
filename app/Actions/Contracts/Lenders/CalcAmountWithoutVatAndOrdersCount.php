<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Lender;
use Cknow\Money\Money;

interface CalcAmountWithoutVatAndOrdersCount
{
    /**
     * Create new user.
     */
    public function handle(Lender $lender, Money $chargeAmountWithVat): array;
}
