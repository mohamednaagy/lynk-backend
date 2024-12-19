<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;

class NoEligibleCommoditiesAvailableStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::NoEligibleCommoditiesAvailable);
        $data['case'] = OrderStatus::NoEligibleCommoditiesAvailable;
        $data['external_order_no'] = $this->localMarketOrder->external_order_no;
        $this->localMarketWebhook->with($data)->handle();
        $this->logQueueJob('Notify our customer sorry we can not find your eligible commodities');
    }
}
