<?php

namespace App\Support\Traders\Traits;

use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;

trait StopsTraderOrderOnJobFailure
{
    public function failed()
    {
        $traderOrder = null;

        if (method_exists($this, 'getTraderOrder')) {
            $traderOrder = $this->getTraderOrder();
        } elseif (property_exists($this, 'traderOrderId')) {
            $traderOrder = TraderOrder::query()->find($this->traderOrderId);
        }

        if (! $traderOrder) {
            return;
        }

        $traderOrder->update([
            'status' => TraderOrderStatus::FailureToProgress,
        ]);
    }
}
