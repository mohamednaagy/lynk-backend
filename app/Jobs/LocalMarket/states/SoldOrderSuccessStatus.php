<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use Illuminate\Support\Facades\Log;

class SoldOrderSuccessStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // handle rotation or complete order
            $this->localMarketWebhook->with(['case' => OrderStatus::CommoditiesSell, 'external_order_no' => $this->externalOrderNo])->handle();
            $this->logQueueJob('Order Sold successfully');
        } catch (\Exception $e) {
            Log::channel('local_market')->error("failed sold order success status, local market order id {$this->localMarketOrder->id}", ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
