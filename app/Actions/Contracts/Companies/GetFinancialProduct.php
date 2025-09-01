<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Company;

interface GetFinancialProduct
{
    public function handle(Company $lender);
}
