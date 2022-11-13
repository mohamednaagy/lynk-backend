<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\ApproveOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\User;

class ApproveOrderAction implements ApproveOrder
{
    public function handle(FinancingOrder $financingOrder, User $user)
    {
        $financingOrder->status = FinancingOrderStatus::WaitingClientWakala;
        $financingOrder->approver_id = $user->id;
        $financingOrder->approved_at = now();
        $financingOrder->save();
    }
}
