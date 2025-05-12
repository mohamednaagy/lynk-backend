<?php

namespace App\Support\Traders\Traits;

use App\Models\TraderOrder;
use Illuminate\Support\Facades\Log;

trait StopsTraderOrderOnJobFailure
{
    public function failed($exception)
    {
        $traderOrder = null;
        $channel = 'default';

        if (method_exists($this, 'getTraderOrder')) {
            $traderOrder = $this->getTraderOrder();
        } elseif (property_exists($this, 'traderOrderId')) {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);
        }

        if (! $traderOrder) {
            return;
        }

        // To be stopped temoporarily until UI is ready
        // $traderOrder->update([
        //     'can_continue_progress' => false,
        // ]);
        if (property_exists($this, 'channel')) {
            $channel = $this->channel;
        }

        if (method_exists($exception, 'getMessage')) {
            Log::channel($channel)->error($exception->getMessage());
        }
    }
}
