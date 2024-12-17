<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Exceptions\LocalMarket\JobStatusException;

class EligibleCommoditiesFoundStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            app(BuyCommodities::class)->handle($this->localMarketOrder);
            $this->logQueueJob('success buy commodity step');
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::EligibleCommoditiesAvailable);
        } catch (\Exception $e) {
            throw new JobStatusException($e->getMessage(), 'failed eligible commodities found status', $this->localMarketOrderID);
        }
    }
}
