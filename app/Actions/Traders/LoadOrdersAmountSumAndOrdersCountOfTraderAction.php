<?php

namespace App\Actions\Traders;

use App\Actions\Contracts\Traders\LoadOrdersAmountSumAndOrdersCountOfTrader;
use App\Enums\TraderOrderStatus;
use App\Models\Company;

class LoadOrdersAmountSumAndOrdersCountOfTraderAction implements LoadOrdersAmountSumAndOrdersCountOfTrader
{
    /**
     * @param  \App\Models\Company  $company
     * @return mixed
     */
    public function handle(Company $trader)
    {
        return Company::query()
            ->leftJoin('trader_orders', 'companies.driver', '=', 'trader_orders.provider')
            ->leftJoin('financing_orders', 'trader_orders.financing_order_id', '=', 'financing_orders.id')
            ->select('companies.*')
            ->selectRaw('COUNT(DISTINCT trader_orders.financing_order_id) as orders_count')
            ->selectRaw('SUM(DISTINCT financing_orders.amount) as orders_sum_amount')
            ->where('companies.id', $trader->id)
            ->where('trader_orders.status', TraderOrderStatus::$inProgressOrComplete)
            ->groupBy('companies.id')
            ->first();
    }
}
