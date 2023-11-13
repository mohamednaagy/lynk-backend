<?php

namespace App\Support\Traders\Traits;

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
            $traderOrder = TraderOrder::query()
                ->lockForUpdate()
                ->find($this->traderOrderId);
        }

        if (! $traderOrder) {
            return;
        }

        $traderOrder->update([
            'can_continue_progress' => false,
        ]);

        if (method_exists($exception, 'getMessage')) {
            Log::error($exception->getMessage());
        }
    }
}
