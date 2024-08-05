<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarketInventoryStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteInventoryStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected LocalMarketInventory $inventory)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            Log::info("Starting transaction for Deleting inventory ID: {$this->inventory->id}");

            // Store the old status
            $oldStatus = $this->inventory->status;

            $this->inventory->update(['status' => LocalMarketInventoryStatus::Pending]);
            LocalMarketInventoryUnits::where('local_market_inventory_id', $this->inventory->id)
                ->delete();
            Log::info("Successfully soft deleted units for inventory ID: {$this->inventory->id}");

            // Soft delete the inventory
            $this->inventory->delete();

            // Restore the old status
            $this->inventory->update(['status' => $oldStatus]);
            Log::info("Restored inventory ID: {$this->inventory->id} status to {$oldStatus}");

            // Commit the transaction
            DB::commit();
            Log::info("Transaction committed for deleting inventory ID: {$this->inventory->id}");
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            $this->inventory->update([
                'status' => LocalMarketInventoryStatus::Problem,
            ]);
            Log::error("Updated inventory ID: {$this->inventory->id} status to Problem due to error: {$e->getMessage()}");
        }
    }
}
