<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Exceptions\ErrorCreatingUnitsForThisINventory;
use App\Exceptions\FailedDecreaseUnitsForInventory;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected LocalMarketInventory $inventory, protected $total, protected $inventoryWasRecentlyCreated = false)
    {
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

            $this->inventory->update(['status' => LocalMarketInventoryStatus::Pending]);

            if ($this->inventoryWasRecentlyCreated) {
                $this->createItemUnits($this->inventory, $this->inventory->available_quantity);
            } else {
                if ($this->total > $this->inventory->total_items) {
                    $this->createItemUnits($this->inventory, $this->total - $this->inventory->total_items);
                } elseif ($this->total < $this->inventory->total_items) {
                    $this->decreaseItemUnits($this->inventory, $this->inventory->total_items - $this->total);
                }
            }

            // Enable inventory (set status to active)
            $this->inventory->update([
                'status' => LocalMarketInventoryStatus::Active,
                'available_quantity' => $this->total - $this->inventory->reserved_items,
            ]);
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
        }
    }

    public function createItemUnits(LocalMarketInventory $inventory, $numberOfUnits)
    {
        Log::info("Increasing units by: {$numberOfUnits} for inventory ID: {$inventory->id}");

        try {
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
                        'qr_code' => $baseName.'-'.$uuid,
                    ];
                }

                Log::info("Inserting {$chunkSize} inventory units for inventory ID: {$inventory->id}");
                $inventory->units()->createMany($inventoryUnits);
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
        Log::info("Decreasing units by: {$decreased_amount}");

        try {
            // Fetch IDs of units to be deleted
            $ids = LocalMarketInventoryUnits::select('id')
                ->where('local_market_inventory_id', $inventory->id)
                ->where('status', (int) LocalMarketInventoryUnitsStatus::Free)
                ->limit($decreased_amount)
                ->delete('id');

        } catch (\Exception $e) {
            DB::rollBack();
            throw new FailedDecreaseUnitsForInventory();
        }
    }
}
