<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderCancelledBy;
use App\Enums\LocalMarket\OrderCancelReason;
use App\Enums\LocalMarket\OrderStatus;
use App\Exceptions\LocalMarket\JobStatusException;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\UnitService;
use Illuminate\Support\Facades\DB;

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
            $this->logQueueJob('add cancel log data to db successfully');
            $this->unitService->revertInventoryUnitOwnership($this->localMarketOrder);
            $this->logQueueJob('swap ownership successfully');
            $this->inventoryService->cancelOrderUnits($this->localMarketOrder);
            $this->logQueueJob('refresh order inventories and units successfully');

            DB::commit();
            $this->localMarketOrder->changeStatusTo(OrderStatus::Cancelled);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->localMarketOrder->changeStatusTo(OrderStatus::FailedToCancel);
            throw new JobStatusException($e->getMessage(), 'failed to exec transaction at pending cancel', $this->localMarketOrderID);
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
