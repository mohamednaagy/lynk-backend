<?php

namespace App\Observers;

use App\Enums\TraderOrderTimeLimitAction;
use App\Enums\TraderOrderTimeLimitStatus;
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
        if ($traderOrderTimeLimit->status === TraderOrderTimeLimitStatus::Pending && 
            $traderOrderTimeLimit->action === TraderOrderTimeLimitAction::AutoCancelOrder) {
            ExpireOrderJob::dispatch($traderOrderTimeLimit->id)->delay(
                Carbon::parse($traderOrderTimeLimit->effective_at)
            );
        }
    }
}
