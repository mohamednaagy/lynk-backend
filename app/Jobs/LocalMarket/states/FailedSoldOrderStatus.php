<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;

class FailedSoldOrderStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::FailedSell);
        $this->localMarketWebhook->with(['case' => OrderStatus::FailedSell, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Failed Order Sold successfully');
    }
}
