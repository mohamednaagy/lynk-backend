<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\RejectOrder;
use App\Enums\FinancingOrderRejectionReason;
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
        $financingOrder->rejectionDetail()->create([
            'creator_id' => $user->id,
            'rejection_reason' => FinancingOrderRejectionReason::Rejected,
            'comment' => $data['status_reason'],
        ]);
    }
}
