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
    protected $originalTotalItems;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LocalMarketInventory $inventory, $originalTotalItems)
    {
        $this->inventory = $inventory;
        $this->originalTotalItems = $originalTotalItems;
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

            $newTotalItems = $this->inventory->total_items;
            Log::info("Original total items: {$this->originalTotalItems}, New total items: {$this->inventory}");

            if ($newTotalItems > $this->originalTotalItems) {
                $newUnits = $newTotalItems - $this->originalTotalItems;
                Log::info("Increasing units by: {$newUnits}");
                app(InventoryItemUnitsService::class)->createItemUnits($this->inventory, $newUnits);
            } elseif ($newTotalItems < $this->originalTotalItems) {
                $unitsToRemove = $this->originalTotalItems - $newTotalItems;
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

            if($this->inventory->total_items !== $this->inventory->units()->count()) {
                DB::rollBack();
                $this->inventory->update([
                    'status' => LocalMarketInventoryStatus::Problem,
                ]);
                Log::error("Transaction rolled back for updating inventory ID: {$this->inventory->id}");
                throw new NeedManuallyCheckUnitsAndStatus();
            }

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
