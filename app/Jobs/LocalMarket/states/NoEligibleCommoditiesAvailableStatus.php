<?php

namespace App\Jobs\LocalMarket\states;

use App\Models\LocalMarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NoEligibleCommoditiesAvailableStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(private LocalMarketOrder $localMarketOrder)
    {
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Nagy Continue this function
        Log::info('Notify our customer sorry we can not find your eligibilities commodities ');
    }
}
