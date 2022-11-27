<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;

class CancelOrderAction implements CancelOrder
{
    public function handle(FinancingOrder $financingOrder, User $user, array $data): void
    {
        $traderOrder = $financingOrder->activeTraderOrder()->first();

        $trader = Trader::driver($traderOrder->provider);

        $trader->cancelOrder($financingOrder);

        // use "update" method to be consistent through the application
        $financingOrder->status = FinancingOrderStatus::PendingCancellation;
        $financingOrder->status_reason = $data['status_reason'] ?? null;
        $financingOrder->save();
    }
}
