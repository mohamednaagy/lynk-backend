<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Lender;

interface GetFinancingOrderType
{
    public function handle(Lender $lender);
}
