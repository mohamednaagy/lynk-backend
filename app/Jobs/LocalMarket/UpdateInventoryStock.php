<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Exceptions\ErrorCreatingUnitsForThisINventory;
use App\Exceptions\FailedDecreaseUnitsForInventory;
use App\Exceptions\NeedManuallyCheckUnitsAndStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Services\InventoryItemUnitsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Nonstandard\Uuid;

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
            Log::info("Original total items: {$this->originalTotalItems}, New total items: {$this->inventory->id}");

            if ($newTotalItems > $this->originalTotalItems) {
                $newUnits = $newTotalItems - $this->originalTotalItems;
                Log::info("Increasing units by: {$newUnits}");
                $this->createItemUnits($this->inventory, $newUnits);
            } elseif ($newTotalItems < $this->originalTotalItems) {
                $unitsToRemove = $this->originalTotalItems - $newTotalItems;
                if ($unitsToRemove > 0) {
                    Log::info("Decreasing units by: {$unitsToRemove}");
                    $this->decreaseItemUnits($this->inventory, $unitsToRemove);
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
            Log::error("Transaction rolled back for updating inventory ID: {$this->inventory->id}. Error: {$this->originalTotalItems}");

            $this->inventory->available_quantity = $this->originalTotalItems;
            $this->inventory->saveQuietly(); 

            Log::error("Transaction rolled back for updating inventory ID: {$this->inventory}");
            //throw new NeedManuallyCheckUnitsAndStatus();
        }
    }


    public function createItemUnits(LocalMarketInventory $inventory, $numberOfUnits)
    {
        try {
            // TODO no need to recalculate numberOfUnits
            $chunkSize = ($numberOfUnits <= 20000) ? $numberOfUnits : 20000;
            $numberOfChunks = ceil($numberOfUnits / $chunkSize); // Use ceil to ensure covering all units

            //loop through the chunks
            for ($i = 0; $i < $numberOfChunks; $i++) {
                $isLastChunk = ($i == $numberOfChunks - 1);
                if ($isLastChunk) { // Get if this is the last chunk
                    $chunkSize = $numberOfUnits - ($i * $chunkSize);
                }

                //dispatch job
                $inventoryUnits = [];
                $baseName = $inventory->generateQrCodeBaseName();
    
                for ($j = 0; $j < $chunkSize; $j++) {
                    $uuid = Uuid::uuid4()->toString();
                    $inventoryUnits[] = [
                        'local_market_inventory_id' => $inventory->id,
                        'commodity_item_id' => $inventory->item->id,
                        'qr_code' => $baseName . '-' . $uuid,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
    
                Log::info("Inserting {$chunkSize} inventory units for inventory ID: {$inventory->id}");
                LocalMarketInventoryUnits::insert($inventoryUnits);
                if ($isLastChunk) {
                    $totalUnitsCreated = $inventory->CountOfUnits();
                    $availableQuantity = $inventory->available_quantity;
    
                    Log::info("Total units created: {$totalUnitsCreated}, Available quantity: {$availableQuantity}");
    
                    // Update the inventory status based on the unit count
                    if ($totalUnitsCreated == $availableQuantity) {
                        Log::info("Inventory ID: {$inventory->id} set to Active status");
                    } elseif ($totalUnitsCreated < $availableQuantity) {
                        $missingUnits = $availableQuantity - $totalUnitsCreated;
                        Log::info("Dispatching additional job for {$missingUnits} missing units for inventory ID: {$inventory->id}");
                        //self::dispatch($this->inventory, $missingUnits, true)->onQueue('unit-inventory');
                    }
                }
    
                unset($inventoryUnits);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ErrorCreatingUnitsForThisINventory();
        }
    }

    public function decreaseItemUnits(LocalMarketInventory $inventory, $decreased_amount)
    {
        try {
            // Fetch IDs of units to be deleted
            $ids = LocalMarketInventoryUnits::select('id')
                ->where('local_market_inventory_id', $inventory->id)
                ->where('status', (int) LocalMarketInventoryUnitsStatus::Free)
                ->limit($decreased_amount)
                ->pluck('id');

            // Delete the fetched rows by IDs
            LocalMarketInventoryUnits::whereIn('id', $ids)->delete();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new FailedDecreaseUnitsForInventory();
        }
    }
}
