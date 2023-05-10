<?php

namespace App\Support\Traders\Traits;

use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;

trait StopsTraderOrderOnJobFailure
{
    public function failed($exception)
    {
        $traderOrder = TraderOrder::query()->find($this->traderOrderId);

        if (! $traderOrder) {
            return;
        }

        $traderOrder->update([
            'status' => TraderOrderStatus::FailureToProgress,
        ]);
    }
}
