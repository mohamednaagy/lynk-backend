<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Lender;

interface ChargeLenderBalanceManually
{
    public function handle(Lender $lender, array $data);
}
