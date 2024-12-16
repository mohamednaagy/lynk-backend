<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use Illuminate\Support\Facades\Log;

class FailedPurchaseStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Nagy Continue this function
        // use webhook to notify the user
        try {
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::FailedPurchase);
            $this->localMarketWebhook->with(['case' => OrderStatus::FailedPurchase, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
            Log::channel('local_market')->info('Sorry there is an error while purchasing commodities for order ');
        } catch (\Exception $e) {
            Log::channel('local_market')->error("failed purchase, local market order id {$this->localMarketOrder->id}", ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
