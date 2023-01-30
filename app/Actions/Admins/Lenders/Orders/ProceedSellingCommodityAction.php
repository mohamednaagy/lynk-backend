<?php

namespace App\Actions\Admins\Lenders\Orders;

use App\Actions\Contracts\Admins\Lenders\Orders\ProceedSellingCommodity;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Support\Facades\DB;

class ProceedSellingCommodityAction implements ProceedSellingCommodity
{
    public function handle(FinancingOrder $financingOrder)
    {
        DB::transaction(function () use ($financingOrder) {
            $traderOrder = $financingOrder->activeTraderOrder()->first();

            $financingOrder->update([
                'status' => FinancingOrderStatus::CommoditySoldToCustomer,
            ]);

            $traderOrder->traderHistories()->create([
                'action' => FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
            ]);
        });
    }
}
