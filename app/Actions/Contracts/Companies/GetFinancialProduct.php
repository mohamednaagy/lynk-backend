<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Lender;

interface GetFinancialProduct
{
    public function handle(Lender $lender);
}
