<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Enums\LocalMarket\OrderHistoryStatus;
use Illuminate\Support\Facades\Log;

class EligibleCommoditiesFoundStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            app(BuyCommodities::class)->handle($this->localMarketOrder);
            Log::channel('local_market')->info("success buy commodity step to local market id {$this->localMarketOrderID} ");
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::EligibleCommoditiesAvailable);
        } catch (\Exception $e) {
            Log::channel('local_market')->error("failed eligible local market order id {$this->localMarketOrderID}", ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
