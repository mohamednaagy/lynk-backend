<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;
use Illuminate\Support\Facades\DB;

class CancelOrderAction implements CancelOrder
{
    public function handle(FinancingOrder $financingOrder, User $user, array $data): void
    {
        $driver = config('trader.default');
        $trader = Trader::driver($driver);
        DB::transaction(function () use ($trader, $financingOrder, $data) {
            $trader->cancelOrder($financingOrder);

            $financingOrder->status = FinancingOrderStatus::PendingCancellation;
            $financingOrder->status_reason = $data['status_reason'] ?? null;
            $financingOrder->save();
        });
    }
}
