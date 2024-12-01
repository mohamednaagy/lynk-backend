<?php

namespace App\Jobs\LocalMarket;

use App\Models\CommodityItem;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\LiveMarketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteCommodityItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected CommodityItem $commodityItem)
    {
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(LiveMarketService $liveMarketService, InventoryService $inventoryService): void
    {
        try {
            DB::transaction(function () use ($liveMarketService, $inventoryService) {
                $this->logStartDeletion();

                $this->deleteRelatedInventories($inventoryService);
                $this->deleteLiveMarketRecords($liveMarketService);
                $this->deleteCommodityItem();

                $this->logSuccessfulDeletion();
            });
        } catch (\Exception $e) {
            $this->handleError($e);
            throw $e;
        }
    }

    /**
     * Delete all related inventories
     */
    private function deleteRelatedInventories(InventoryService $inventoryService): void
    {
        $this->commodityItem->inventories->each(function ($inventory) use ($inventoryService) {
            $inventoryService->deleteInventory($inventory);
        });
    }

    /**
     * Delete live market records
     */
    private function deleteLiveMarketRecords(LiveMarketService $liveMarketService): void
    {
        $liveMarketService->handleCommodityItemDeletion($this->commodityItem);
    }

    /**
     * Delete the commodity item
     */
    private function deleteCommodityItem(): void
    {
        $this->commodityItem->delete();
    }

    /**
     * Handle error during deletion
     */
    private function handleError(\Exception $e): void
    {
        Log::error("Failed to delete CommodityItem ID: {$this->commodityItem->id}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Log start of deletion process
     */
    private function logStartDeletion(): void
    {
        Log::info("Starting deletion process for CommodityItem ID: {$this->commodityItem->id}");
    }

    /**
     * Log successful deletion
     */
    private function logSuccessfulDeletion(): void
    {
        Log::info("Successfully deleted CommodityItem ID: {$this->commodityItem->id} and all related records");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("DeleteCommodityItem job failed for CommodityItem ID: {$this->commodityItem->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
