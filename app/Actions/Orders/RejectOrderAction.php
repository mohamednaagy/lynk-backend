<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\RejectOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class RejectOrderAction implements RejectOrder
{
    public function handle(FinancingOrder $financingOrder, User|Authenticatable $user, array $data): void
    {
        $financingOrder->status = FinancingOrderStatus::Rejected;
        $financingOrder->reason = $data['reason'] ?? null;
        $financingOrder->save();
    }
}
