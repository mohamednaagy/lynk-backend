<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;

class FailedPurchaseStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // use webhook to notify the user
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::FailedPurchase);
        $this->localMarketWebhook->with(['case' => OrderStatus::FailedPurchase, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Sorry there is an error while purchasing commodities');
    }
}
