<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Company;

interface GetTransactions
{
    public function handle(Company $company, array $data): mixed;
}
