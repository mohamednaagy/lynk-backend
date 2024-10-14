<?php

namespace App\Jobs\LocalMarket;

use App\Models\CommodityItem;
use App\Services\LocalMarket\InventoryService;
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
     *
     * @return void
     */
    public function __construct(protected CommodityItem $commodityItem) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            Log::info("Starting transaction for Deleting CommodityItem ID: {$this->commodityItem->id}");

            foreach ($this->commodityItem->inventories as $inventory) {
                InventoryService::deleteInventory($inventory);
            }

            $this->commodityItem->delete();

            DB::commit();
            Log::info("Transaction committed for deleting inventory ID: {$this->commodityItem->id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Updated Commodity Item ID: {$this->commodityItem->id} status to Problem due to error: {$e->getMessage()}");
        }
    }
}
