<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Company;

interface ChargeLenderBalanceManually
{
    public function handle(Company $company, array $data);
}
