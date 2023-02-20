<?php

namespace App\Actions\Contracts\Traders;

use App\Models\Company;

interface GetOrdersAmountSumAndOrdersCountOfTrader
{
    public function handle(Company $trader);
}
