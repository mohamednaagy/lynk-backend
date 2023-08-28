<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Company;
use Cknow\Money\Money;

interface CalcAmountWithoutVatAndOrdersCount
{
    /**
     * Create new user.
     */
    public function handle(Company $company, Money $chargeAmountWithVat): array;
}
