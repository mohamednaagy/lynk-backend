<?php

namespace App\Support\Traders\Traits;

use App\Enums\TraderOrderStatus;

trait StopsTraderOrderOnJobFailure
{
    public function failed($exception)
    {
        $this->traderOrder->update([
            'status' => TraderOrderStatus::FailureToProgress,
        ]);
    }
}
