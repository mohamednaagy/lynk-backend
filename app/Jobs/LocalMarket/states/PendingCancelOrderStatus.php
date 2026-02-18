<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderCancelledBy;
use App\Enums\LocalMarket\OrderCancelReason;
use App\Enums\LocalMarket\OrderStatus;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\UnitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        DB::statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
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
            $this->logFailure($e);

            throw $e;
        }
    }

    private function logCancellationDetails(): void
    {
        $this->localMarketOrder->cancelOrder()->create([
            'cancelled_by' => OrderCancelledBy::Customer,
            'cancel_reason' => OrderCancelReason::CancelOrder,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->localMarketOrder->changeStatusTo(OrderStatus::FailedToCancel);
        $this->logFailure($exception);
    }

    private function logFailure(Throwable $exception): void
    {
        $errorMessage = formatLocalMarketOrderTitle("failed {$this->className}, the given ", $this->localMarketOrder);
        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(
            $errorMessage,
            [
                'attempt' => $this->attempts(),
                'localMarketOrderId' => $this->localMarketOrderID,
                'order_reference' => $this->localMarketOrder->external_order_no,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }
}
