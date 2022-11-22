<?php

namespace App\Actions\Contracts\Admins\Transactions;

use App\Models\Company;

interface ChargeBalanceManually
{
    public function handle(Company $company, array $data);
}
