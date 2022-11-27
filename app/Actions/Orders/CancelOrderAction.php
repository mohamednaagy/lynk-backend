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
        // __REVIEW__ You should get the active TraderOrder and use its driver
        $driver = config('trader.default');
        $trader = Trader::driver($driver);

        // __REIVEW__ remove DB::transaction(...) to be outside action to give more flexibility
        DB::transaction(function () use ($trader, $financingOrder, $data) {
            $trader->cancelOrder($financingOrder);

            // use "update" method to be consistent through the application
            $financingOrder->status = FinancingOrderStatus::PendingCancellation;
            $financingOrder->status_reason = $data['status_reason'] ?? null;
            $financingOrder->save();
        });
    }
}
