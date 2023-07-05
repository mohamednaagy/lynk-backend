<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\RejectOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\User;

class RejectOrderAction implements RejectOrder
{
    public function handle(FinancingOrder $financingOrder, User $user, array $data): void
    {
        $financingOrder->status = FinancingOrderStatus::Rejected;
        $financingOrder->status_reason = $data['status_reason'] ?? null;
        $financingOrder->approved_at = null;
        $financingOrder->save();
    }
}
