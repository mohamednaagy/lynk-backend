<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Enums\LocalMarketOrderHistoryStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class EligibleCommoditiesFoundStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable;

    public function __construct(private int $localMarketOrderID)
    {
        $this->onQueue('local_market');
        Log::channel('local_market')->info("add EligibleCommoditiesFoundStatus job to queue local_market with local market id {$this->localMarketOrderID} ");

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $localMarketOrder = LocalMarketOrder::findOrFail($this->localMarketOrderID);
            app(BuyCommodities::class)->handle($localMarketOrder);
            Log::channel('local_market')->info("success buy commodity step to local market id {$this->localMarketOrderID} ");
            $this->createLocalMarketOrderHistory($localMarketOrder, LocalMarketOrderHistoryStatus::EligibleCommoditiesAvailable);
        } catch (\Exception $e) {
            Log::channel('local_market')->error("failed eligible local market order id {$this->localMarketOrderID}", ['message' => $e->getMessage()]);
            throw $e;
        }

    }
}
