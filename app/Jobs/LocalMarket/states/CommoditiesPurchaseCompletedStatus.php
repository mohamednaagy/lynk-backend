<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use App\Jobs\LocalMarket\CommoditiesSettlement\DispatchOrderSettlementCheck;

class CommoditiesPurchaseCompletedStatus extends BaseStatus
{
    public function __construct(protected int $localMarketOrderID)
    {
        parent::__construct($localMarketOrderID);
        $this->onQueue('complete_commodities_purchased_local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->getDataOfLocalMarketOrder($this->localMarketOrder);
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::CommoditiesPurchased);
        $data['case'] = OrderStatus::CommoditiesPurchased;
        $data['external_order_no'] = $this->localMarketOrder->external_order_no;
        $this->localMarketWebhook->with($data)->handle();
        $this->logQueueJob('Congratulations Commodities purchased');

        // Dispatch a job to verify the settlement status of orders that require settlement.
        DispatchOrderSettlementCheck::dispatch($this->localMarketOrder->id);
    }
}
