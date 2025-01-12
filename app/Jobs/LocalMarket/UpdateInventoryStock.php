<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarket\InventoryStatus;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Exceptions\ErrorCreatingUnitsForThisINventory;
use App\Exceptions\FailedDecreaseUnitsForInventory;
use App\Models\LocalMarketInventory;
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

    protected LocalMarketInventory $inventory;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $inventoryId, protected int $total, protected bool $inventoryWasRecentlyCreated = false)
    {
        $this->inventory = LocalMarketInventory::findOrFail($this->inventoryId);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {

            Log::info("Starting transaction for inventory ID: {$this->inventory->id}", [
                'inventory' => $this->inventory,
                'total' => $this->total,
                'inventory_was_recently_created' => $this->inventoryWasRecentlyCreated,
            ]);

            $this->inventory->update(['status' => InventoryStatus::Pending]);

            if ($this->inventoryWasRecentlyCreated) {
                $this->createItemUnits($this->inventory, $this->inventory->available_quantity);
            } else {
                // Handle both increase and decrease in one transaction
                $difference = $this->total - $this->inventory->total_items;
                Log::info("Difference: {$difference} for inventory ID: {$this->inventory->id}");
                $difference > 0 ? $this->createItemUnits($this->inventory, $difference) : $this->decreaseItemUnits($this->inventory, abs($difference));
            }

            $this->inventory->refreshStockQuantities();
            $this->inventory->update(['status' => InventoryStatus::Active]);

            Log::info("Successfully updated inventory ID: {$this->inventory->id}");
        } catch (\Exception $e) {
            $this->inventory->update(['status' => InventoryStatus::Problem]);
            Log::error('Error in transaction for inventory ID: '.$this->inventory->id, ['error' => $e]);
            throw $e;
        }
    }

    public function createItemUnits(LocalMarketInventory $inventory, $numberOfUnits)
    {
        Log::info("Increasing units by: {$numberOfUnits} for inventory ID: {$inventory->id}", [
            'was_recently_created' => $this->inventoryWasRecentlyCreated,
        ]);
        try {
            DB::select('CALL GenerateRandomInventoryUnitsQRCode(?, ? , ?, ?, ?, ?, ?)', [
                $inventory->id,
                $inventory->commodity_item_id,
                $numberOfUnits,
                $inventory->company_id,
                OwnershipTypes::OriginalSupplier,  // TODO aadel double call enum when merge with dev branch
                InventoryUnitsStatus::Free,
                $inventory->generateQrCodeBaseName(),
            ]);
            Log::info("Successfully created {$numberOfUnits} units for inventory ID: {$inventory->id}");
        } catch (\Exception $e) {
            Log::error('Error creating units for inventory ID: '.$inventory->id, [
                'error' => $e,
            ]);
            throw new ErrorCreatingUnitsForThisINventory;
        }
    }

    public function decreaseItemUnits(LocalMarketInventory $inventory, $decreased_amount)
    {
        Log::info("Decreasing units by: {$decreased_amount} for inventory ID: {$inventory->id}");
        try {
            DB::select('CALL DeleteLocalMarketInventoryUnits(?, ? , ?)', [$inventory->id, InventoryUnitsStatus::Free, $decreased_amount]);
            Log::info("Successfully decreased {$decreased_amount} units for inventory ID: {$inventory->id}");
        } catch (\Exception $e) {
            Log::error('Error decreasing units for inventory ID: '.$inventory->id, [
                'error' => $e,
            ]);
            throw new FailedDecreaseUnitsForInventory;
        }
    }
}
