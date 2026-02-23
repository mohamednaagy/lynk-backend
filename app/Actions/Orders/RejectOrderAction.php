<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\RejectOrder;
use App\Enums\FinancingOrderCancelReason;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Traits\TraderHelperTrait;

class RejectOrderAction implements RejectOrder
{
    use TraderHelperTrait;

    public function handle(FinancingOrder $financingOrder, User $user, array $data): void
    {
        $this->updateOrderStatus($financingOrder, FinancingOrderStatus::Rejected);
        if (isset($data['status_reason']) && ! is_null($data['status_reason'])) {
            $financingOrder->cancelDetail()->create([
                'creator_id' => $user->id,
                'cancel_reason' => FinancingOrderCancelReason::Rejected,
                'comment' => $data['status_reason'],
            ]);
        }
    }
}
