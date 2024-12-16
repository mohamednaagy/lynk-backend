<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use Illuminate\Support\Facades\Log;

class NoEligibleCommoditiesAvailableStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::NoEligibleCommoditiesAvailable);
            $data['case'] = OrderStatus::NoEligibleCommoditiesAvailable;
            $data['external_order_no'] = $this->localMarketOrder->external_order_no;
            $this->localMarketWebhook->with($data)->handle();
            Log::channel('local_market')->info('Notify our customer sorry we can not find your eligibilities commodities ');
        } catch (\Exception $e) {
            Log::channel('local_market')->error("failed no eligible commodities available status, local market order id {$this->localMarketOrder->id}", ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
