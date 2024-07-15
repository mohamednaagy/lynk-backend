<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarketInventoryStatus;
use App\Exceptions\NeedManuallyCheckUnitsAndStatus;
use App\Models\LocalMarketInventory;
use App\Services\InventoryItemUnitsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateInventoryStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventory;
    protected $total;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LocalMarketInventory $inventory, $total)
    {
        $this->inventory = $inventory;
        $this->total = $total;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            Log::info("Starting transaction for updating inventory ID: {$this->inventory->id}");

            // Disable inventory (set status to pending)
            $this->inventory->update(['status' => LocalMarketInventoryStatus::Pending]);
            Log::info("Set inventory ID: {$this->inventory->id} to status pending");

            $newTotalItems = $this->total;
            $originalTotalItems = $this->inventory->getOriginal('total_items');
            Log::info("Original total items: {$originalTotalItems}, New total items: {$newTotalItems}");

            if ($newTotalItems > $originalTotalItems) {
                $newUnits = $newTotalItems - $originalTotalItems;
                Log::info("Increasing units by: {$newUnits}");
                app(InventoryItemUnitsService::class)->createItemUnits($this->inventory, $newUnits);
            } elseif ($newTotalItems < $originalTotalItems) {
                $unitsToRemove = $originalTotalItems - $newTotalItems;
                if ($unitsToRemove > 0) {
                    Log::info("Decreasing units by: {$unitsToRemove}");
                    app(InventoryItemUnitsService::class)->decreaseItemUnits($this->inventory, $unitsToRemove);
                }
            }

            // Enable inventory (set status to active)
            $this->inventory->update(['status' => LocalMarketInventoryStatus::Active]);
            Log::info("Set inventory ID: {$this->inventory->id} to status active");

            // Commit the transaction
            DB::commit();
            Log::info("Transaction committed for updating inventory ID: {$this->inventory->id}");

        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            $this->inventory->update([
                'status' => LocalMarketInventoryStatus::Problem,
            ]);
            Log::error("Transaction rolled back for updating inventory ID: {$this->inventory->id}. Error: {$e->getMessage()}");
            throw new NeedManuallyCheckUnitsAndStatus();
        }
    }
}
