<?php

namespace App\Support\Traders\Traits;

use App\Enums\FinancingOrderStatus;
use App\Models\TraderOrder;
use Illuminate\Support\Facades\DB;

trait StopsTraderOrderOnJobFailure
{
    public function failed($exception)
    {
        $traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrder);

        DB::transaction(function () use ($traderOrder) {
            $traderOrder->update([
                'status' => FinancingOrderStatus::FailureToProgress,
            ]);
        });
    }
}
