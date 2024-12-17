<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Enums\LocalMarket\OrderHistoryStatus;

class EligibleCommoditiesFoundStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(BuyCommodities::class)->handle($this->localMarketOrder);
        $this->logQueueJob('success buy commodity step');
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::EligibleCommoditiesAvailable);
    }
}
