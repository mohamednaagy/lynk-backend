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
        DB::transaction(function () use ($financingOrder, $data) {
            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->cancelTtiId($financingOrder);

            $financingOrder->status = FinancingOrderStatus::PendingCancel;
            $financingOrder->status_reason = $data['status_reason'] ?? null;
            $financingOrder->save();
        });
    }
}
