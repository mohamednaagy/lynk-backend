<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Services\LocalMarket\UnitService;
use Illuminate\Support\Facades\Log;

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
        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('we will start  changeOrderUnitsOwnershipTo at TransferCommodityToCustomerStatus job ', $this->localMarketOrder));

        $this->unitService->changeOrderUnitsOwnershipTo(
            $this->localMarketOrder,
            OwnershipTypes::Customer,
            $this->localMarketOrder->customer_name,
            UnitOwnershipAction::BorrowerOwnershipTransfer
        );

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('we completed  changeOrderUnitsOwnershipTo at TransferCommodityToCustomerStatus job ', $this->localMarketOrder));

        $this->localMarketWebhook->with(['case' => OrderStatus::TransferOwnershipToCustomer, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('we send webhook to lynk from TransferCommodityToCustomerStatus job with case :'.OrderStatus::TransferOwnershipToCustomer, $this->localMarketOrder));

        $this->logQueueJob('Transfer Ownership to customer successfully');
    }
}
