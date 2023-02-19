<?php

namespace App\Actions\Traders;

use App\Actions\Contracts\Traders\GetOrdersAmountSumAndOrdersCountOfTrader;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Support\Money\Money;

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

        $totalAmount = (new Money($financingOrdersOfTraders->sum('amount'), Money::getDefaultCurrency()))
            ->formatByDecimal();

        $totalAmountFormatted = number_format($totalAmount, 2);

        return [
            'orders_count' => $financingOrdersOfTraders->count(),
            'orders_sum_amount' => $totalAmountFormatted,
        ];
    }
}
