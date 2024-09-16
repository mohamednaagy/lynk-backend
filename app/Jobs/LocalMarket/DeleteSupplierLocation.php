<?php

namespace App\Jobs\LocalMarket;

use App\Models\SupplierLocation;
use App\Services\LocalMarket\InventoryService;
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
    public function __construct(protected SupplierLocation $supplierLocation)
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

            Log::info("Starting transaction for Deleting location ID: {$this->supplierLocation->id}");
            
            foreach ($this->supplierLocation->inventories as $inventory) {
                InventoryService::deleteInventory($inventory);
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
