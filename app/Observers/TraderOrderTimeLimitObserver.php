<?php

namespace App\Observers;

use App\Jobs\TraderOrder\ExpireOrderJob;
use App\Models\TraderOrderTimeLimit;
use Carbon\Carbon;

class TraderOrderTimeLimitObserver
{
    /**
     * Handle the TraderOrderTimeLimit "created" event.
     *
     * @return void
     */
    public function created(TraderOrderTimeLimit $traderOrderTimeLimit)
    {
        $effectiveAt = Carbon::parse($traderOrderTimeLimit->effective_at);

        ExpireOrderJob::dispatch($traderOrderTimeLimit->id)
            ->delay($effectiveAt);
    }
}
