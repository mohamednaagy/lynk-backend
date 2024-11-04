<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelTraderOrder;
use App\Enums\TraderOrderCancelType;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;

class CancelTraderOrderAction implements CancelTraderOrder
{
    public function handle(
        TraderOrder $traderOrder,
        User $user,
        array $data,
        int $cancelReason
    ): void {
        Trader::driver($traderOrder->provider, $traderOrder->version)
            ->cancelTraderOrder($traderOrder, $cancelReason, cancelledByType: TraderOrderCancelType::User, cancelledBy: auth()->user());
    }
}
