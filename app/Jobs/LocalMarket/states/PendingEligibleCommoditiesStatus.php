<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Models\LocalMarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PendingEligibleCommoditiesStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private LocalMarketOrder $localMarketOrder)
    {
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(FindEligibleCommodities $GetSuitableCommoditiesStocks): void
    {
        $GetSuitableCommoditiesStocks->handle($this->localMarketOrder);
    }
}
