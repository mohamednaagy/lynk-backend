<?php

namespace App\Actions\Traders;

use App\Actions\Contracts\Traders\LoadOrdersAmountSumAndOrdersCountOfTrader;
use App\Models\Company;

class LoadOrdersAmountSumAndOrdersCountOfTraderAction implements LoadOrdersAmountSumAndOrdersCountOfTrader
{
    /**
     * @param  \App\Models\Company  $company
     * @return mixed
     */
    public function handle(Company $trader)
    {
        return $trader->loadCount(
            [
                'traderOrders.order',
            ]
        );
    }
}
