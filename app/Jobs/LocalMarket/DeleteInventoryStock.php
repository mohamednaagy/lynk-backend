<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Exceptions\FailedDeleteUnitsForInventory;
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
    public function __construct(protected LocalMarketInventory $inventory) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            Log::info("Starting transaction for Deleting inventory ID: {$this->inventory->id}");

            $this->inventory->update(['status' => LocalMarketInventoryStatus::Deleting]);

            $this->softDeleteUnits($this->inventory);

            // Soft delete the inventory
            $this->inventory->delete();

            // Commit the transaction
            DB::commit();
            Log::info("Transaction committed for deleting inventory ID: {$this->inventory->id}");
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            $this->inventory->update([
                'status' => LocalMarketInventoryStatus::Problem,
            ]);
        }
    }

    public function softDeleteUnits(LocalMarketInventory $inventory)
    {
        try {
            LocalMarketInventoryUnits::where('local_market_inventory_id', $inventory->id)
                ->where('status', (int) LocalMarketInventoryUnitsStatus::Free)
                ->delete();
            Log::info("Successfully soft deleted units for inventory ID: {$inventory->id}");
        } catch (\Exception $e) {
            DB::rollBack();
            throw new FailedDeleteUnitsForInventory;
        }
    }
}
