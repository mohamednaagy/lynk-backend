<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Exceptions\LocalMarket\JobStatusException;
use App\Services\LocalMarket\UnitService;

class TransferCommodityToCustomerStatus extends BaseStatus
{
    private UnitService $unitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->unitService = app(UnitService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->unitService->changeOrderUnitsOwnershipTo($this->localMarketOrder, OwnershipTypes::Customer, $this->localMarketOrder->customer_name, UnitOwnershipAction::BorrowerOwnershipTransfer);
            $this->localMarketWebhook->with(['case' => OrderStatus::TransferOwnershipToCustomer, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
            $this->localMarketOrder->changeStatusTo(OrderStatus::PendingSellCommodities);
            $this->logQueueJob('Transfer Ownership to customer successfully');
        } catch (\Exception $e) {
            throw new JobStatusException($e->getMessage(), 'failed transfer commodity to customer', $this->localMarketOrderID);
        }
    }
}
