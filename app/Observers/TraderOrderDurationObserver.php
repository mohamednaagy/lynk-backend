<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TraderOrderDuration;

class TraderOrderDurationObserver
{
    public function created(TraderOrderDuration $traderOrderDuration): void
    {
        TraderOrderDuration::flushCache();
    }

    public function updated(TraderOrderDuration $traderOrderDuration): void
    {
        TraderOrderDuration::flushCache();
    }

    public function deleted(TraderOrderDuration $traderOrderDuration): void
    {
        TraderOrderDuration::flushCache();
    }
}
