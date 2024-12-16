<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use Illuminate\Support\Facades\Log;

class FailedSoldOrderStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::FailedSell);
            $this->localMarketWebhook->with(['case' => OrderStatus::FailedSell, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
            $this->logQueueJob('Failed Order Sold successfully');
        } catch (\Exception $e) {
            Log::channel('local_market')->error("failed sold order Status, local market order id {$this->localMarketOrder->id}", ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
