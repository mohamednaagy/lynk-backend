<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Company;

interface ChargeBalanceManually
{
    public function handle(Company $company, array $data);
}
