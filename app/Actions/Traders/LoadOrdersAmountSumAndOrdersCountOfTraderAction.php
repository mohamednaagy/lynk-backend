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
        return Company::query()
            ->with([
                'traderOrders' => function ($query) {
                    $query->notCancelled()
                        ->select('id', 'financing_order_id', 'provider', 'status')
                        ->withSum('order', 'amount');
                },
            ])
            ->leftJoin('trader_orders', 'companies.driver', '=', 'trader_orders.provider')
            ->select('companies.*')
            ->selectRaw('COUNT(DISTINCT trader_orders.financing_order_id) as orders_count')
            ->where('companies.id', $trader->id)
            ->groupBy('companies.id')
            ->first();
    }
}
