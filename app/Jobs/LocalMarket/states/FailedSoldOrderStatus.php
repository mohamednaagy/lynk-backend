<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\CaseStatus;
use App\Enums\LocalMarket\OrderHistoryStatus;

class FailedSoldOrderStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::FailedSell);
        $this->localMarketWebhook->with(['case' => CaseStatus::FailedSell, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Failed Order Sold successfully');
    }
}
