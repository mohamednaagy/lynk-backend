<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\UnitService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class PendingSellOrderStatus extends BaseStatus
{
    private InventoryService $inventoryService;

    private UnitService $unitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);
        $this->unitService = app(UnitService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->unitService->changeOrderUnitsOwnershipTo($this->localMarketOrder, OwnershipTypes::TraderOrder, $this->localMarketOrder->external_order_no, UnitOwnershipAction::SellCommodity);
            $this->inventoryService->completeOrderUnits($this->localMarketOrder);
            $this->localMarketOrder->changeStatusTo(OrderStatus::CommoditiesSell);
            $this->logQueueJob('pending successfully');
        } catch (\Throwable $e) {
            $this->localMarketOrder->changeStatusTo(OrderStatus::FailedSell);
            $this->logQueueJob('failed to sell order');
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('failed to sell order', $this->localMarketOrder), [
                'localMarketOrderId' => $this->localMarketOrder->id,
                'order_reference' => $this->localMarketOrder->external_order_no,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->localMarketOrder->id;
    }
}
