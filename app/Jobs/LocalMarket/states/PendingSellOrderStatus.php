<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\UnitService;
use Illuminate\Support\Facades\DB;

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
        DB::beginTransaction();
        try {
            $this->unitService->changeOrderUnitsOwnershipTo($this->localMarketOrder, OwnershipTypes::TraderOrder, $this->localMarketOrder->external_order_no, UnitOwnershipAction::SellCommodity);
            $this->inventoryService->completeOrderUnits($this->localMarketOrder);
            DB::commit();
            $this->localMarketOrder->changeStatusTo(OrderStatus::CommoditiesSell);
            $this->logQueueJob('pending successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->localMarketOrder->changeStatusTo(OrderStatus::FailedSell);
            throw new $e;
        }
    }
}
