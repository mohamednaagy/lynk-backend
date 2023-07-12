<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\Transaction;

interface DeductVatPercentage
{
    public function handle(TraderOrder $traderOrder, Transaction $transaction, Company $company);
}
