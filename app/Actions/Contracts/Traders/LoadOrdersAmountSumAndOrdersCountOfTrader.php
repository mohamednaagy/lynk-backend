<?php

namespace App\Actions\Contracts\Traders;

use App\Models\Company;

interface LoadOrdersAmountSumAndOrdersCountOfTrader
{
    public function handle(Company $trader);
}
