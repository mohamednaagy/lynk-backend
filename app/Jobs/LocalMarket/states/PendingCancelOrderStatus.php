<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderCancelledBy;
use App\Enums\LocalMarket\OrderCancelReason;
use App\Enums\LocalMarket\OrderStatus;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\UnitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PendingCancelOrderStatus extends BaseStatus
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
            $this->logCancellationDetails();
            Log::channel('local_market')->info("add cancel log data to db successfully {$this->localMarketOrder->id}");
            $this->unitService->revertInventoryUnitOwnership($this->localMarketOrder);
            Log::channel('local_market')->info("swap ownership successfully {$this->localMarketOrder->id}");
            $this->inventoryService->cancelOrderUnits($this->localMarketOrder);
            Log::channel('local_market')->info("refresh order inventories and units successfully {$this->localMarketOrder->id}");
            DB::commit();
            $this->localMarketOrder->changeStatusTo(OrderStatus::Cancelled);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::channel('local_market')->error("failed to exec transaction at pending cancel local market order id {$this->localMarketOrder->id}", ['message' => $e->getMessage()]);
            $this->localMarketOrder->changeStatusTo(OrderStatus::FailedToCancel);
        }
    }

    private function logCancellationDetails(): void
    {
        $this->localMarketOrder->cancelOrder()->create([
            'cancelled_by' => OrderCancelledBy::Customer,
            'cancel_reason' => OrderCancelReason::CancelOrder,
        ]);
    }
}
