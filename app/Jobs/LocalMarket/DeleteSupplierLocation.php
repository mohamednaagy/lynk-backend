<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarketInventoryStatus;
use App\Models\LocalMarketInventoryUnits;
use App\Models\SupplierLocation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteSupplierLocation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected SupplierLocation $supplierLocation) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            Log::info("Starting transaction for Deleting location ID: {$this->supplierLocation->id}");

            foreach ($this->supplierLocation->inventories as $inventory) {
                Log::info("Starting transaction for Deleting Inventory ID: {$inventory->id}");
                // Store the old status
                $oldStatus = $inventory->status;

                $inventory->update(['status' => LocalMarketInventoryStatus::Pending]);
                LocalMarketInventoryUnits::where('local_market_inventory_id', $inventory->id)
                    ->delete();
                Log::info("Successfully soft deleted units for inventory ID: {$inventory->id}");

                $inventory->delete();

                // Restore the old status
                $inventory->update(['status' => $oldStatus]);
                Log::info("Restored inventory ID: {$inventory->id} status to {$oldStatus}");
            }

            $this->supplierLocation->delete();

            DB::commit();
            Log::info("Transaction committed for deleting Location ID: {$this->supplierLocation->id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Location ID: {$this->supplierLocation->id} Problem due to error: {$e->getMessage()}");
        }
    }
}
