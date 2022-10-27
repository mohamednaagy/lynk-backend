<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\User;

class CancelOrderAction implements CancelOrder
{
    public function handle(FinancingOrder $financingOrder, User $user, array $data): void
    {
        $financingOrder->status = FinancingOrderStatus::Canceled;
        $financingOrder->status_reason = $data['status_reason'] ?? null;
        $financingOrder->save();
    }
}
