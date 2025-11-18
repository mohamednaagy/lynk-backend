<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\ApproveOrder;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderMode;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Traits\TraderHelperTrait;

class ApproveOrderAction implements ApproveOrder
{
    use TraderHelperTrait;

    public function handle(FinancingOrder $financingOrder, User $user)
    {
        $status = ($financingOrder->company->lender->lenderDetail->require_initiate_trade_request || $financingOrder->company->lender->lenderDetail->trading_mode->is(TraderOrderMode::Manual))
            ? FinancingOrderStatus::PendingTraderOrder
            : FinancingOrderStatus::Approved;

        $this->updateOrderStatus($financingOrder, $status);
        $financingOrder->update([
            'approver_id' => $user->id,
            'approved_at' => now(),
        ]);
    }
}
