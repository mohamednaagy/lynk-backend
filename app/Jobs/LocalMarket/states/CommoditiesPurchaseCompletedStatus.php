<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use Illuminate\Support\Facades\Log;

class CommoditiesPurchaseCompletedStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $data = $this->getDataOfLocalMarketOrder($this->localMarketOrder);
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::CommoditiesPurchased);
            $data['case'] = OrderStatus::CommoditiesPurchased;
            $data['external_order_no'] = $this->localMarketOrder->external_order_no;
            $this->localMarketWebhook->with($data)->handle();
            Log::channel('local_market')->info("Congratulations Commodities purchased for order {$this->localMarketOrder->id}");
        } catch (\Exception $e) {
            Log::channel('local_market')->error("failed commodities purchase, local market order id {$this->localMarketOrder->id}", ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
