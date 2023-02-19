<?php

namespace App\Actions\Traders;

use App\Actions\Contracts\Traders\GetOrdersAmountSumAndOrdersCountOfTrader;
use App\Enums\TraderOrderStatus;
use App\Models\Company;

class GetOrdersAmountSumAndOrdersCountOfTraderAction implements GetOrdersAmountSumAndOrdersCountOfTrader
{
    /**
     * @param  \App\Models\Company  $company
     * @return mixed
     */
    public function handle(Company $trader)
    {
        $financingOrdersOfTraders = $trader
            ->traderOrders()
            ->leftJoin('financing_orders', 'trader_orders.financing_order_id', '=', 'financing_orders.id')
            ->whereIn('trader_orders.status', TraderOrderStatus::$inProgressOrComplete)
            ->select('financing_order_id', 'amount')
            ->groupBy('financing_order_id')
            ->get();

        return [
            'ordersCount' => $financingOrdersOfTraders->count(),
            'ordersSumAmount' => $financingOrdersOfTraders->sum('amount'),
        ];
    }
}
