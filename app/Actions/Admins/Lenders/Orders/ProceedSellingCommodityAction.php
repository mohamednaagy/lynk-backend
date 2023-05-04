<?php

namespace App\Actions\Admins\Lenders\Orders;

use App\Actions\Contracts\Admins\Lenders\Orders\ProceedSellingCommodity;
use App\Enums\FinancingOrderHistory;
use App\Models\FinancingOrder;
use App\Support\Traders\Traits\DmccTraderHelperTrait;
use Illuminate\Support\Facades\DB;

class ProceedSellingCommodityAction implements ProceedSellingCommodity
{
    use DmccTraderHelperTrait;

    public function handle(FinancingOrder $financingOrder)
    {
        DB::transaction(function () use ($financingOrder) {
            $traderOrder = $financingOrder->activeTraderOrder()->first();

            $this->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument
            );
        });
    }
}
