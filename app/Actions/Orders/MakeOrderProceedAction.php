<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\ApproveOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;

class MakeOrderProceedAction implements ApproveOrder
{
    public function handle(FinancingOrder $financingOrder)
    {
        $financingOrder->status = FinancingOrderStatus::InProgress;
        $financingOrder->approver_id = $user->id;
        $financingOrder->approved_at = now();
        $financingOrder->save();

        Trader::driver('dmcc')->getTti($financingOrder);
    }
}
