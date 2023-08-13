<?php

namespace App\Support\Traders\Traits;

use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use Illuminate\Support\Facades\Log;

trait StopsTraderOrderOnJobFailure
{
    public function failed($exception)
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

        Log::error($exception->getMesage());
    }
}
