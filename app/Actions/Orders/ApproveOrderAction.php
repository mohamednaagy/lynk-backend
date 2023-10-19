<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\ApproveOrder;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderMode;
use App\Models\FinancingOrder;
use App\Models\User;

class ApproveOrderAction implements ApproveOrder
{
    public function handle(FinancingOrder $financingOrder, User $user)
    {
        $status = ($financingOrder->company->require_initiate_trade_request || $financingOrder->company->trading_mode->is(TraderOrderMode::Manual))
            ? FinancingOrderStatus::PendingTraderOrder
            : FinancingOrderStatus::Approved;

        $financingOrder->status = $status;
        $financingOrder->approver_id = $user->id;
        $financingOrder->approved_at = now();
        $financingOrder->save();
    }
}
