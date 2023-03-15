<?php

namespace App\Actions\Admins\Lenders\Orders;

use App\Actions\Contracts\Admins\Lenders\Orders\ProceedSellingCommodity;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Support\Facades\DB;

class ProceedSellingCommodityAction implements ProceedSellingCommodity
{
    use TraderHelperTrait;

    public function handle(FinancingOrder $financingOrder)
    {
        DB::transaction(function () use ($financingOrder) {
            $traderOrder = $financingOrder->activeTraderOrder()->first();

            $financingOrder->update([
                'status' => FinancingOrderStatus::CommoditySoldToCustomer,
            ]);

            $this->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::CreateSellingCommodityToCustomerDocument
            );
        });
    }
}
