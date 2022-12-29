<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\Transaction;

interface DeductVatPercentage
{
    public function handle(FinancingOrder $financingOrder, Transaction $transaction, Company $company);
}
