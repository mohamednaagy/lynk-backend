<?php

namespace App\Jobs\LocalMarket;

use App\Exceptions\NeedManuallyCheckUnitsAndStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class CreateLocalMarketUnitInventoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventory;
    protected $chunkSize;
    protected $isLastChunk;

    /**
     * Create a new job instance.
     *
     * @param LocalMarketInventory $inventory
     * @param int $chunkSize
     * @param bool $isLastChunk
     * @return void
     */
    public function __construct(LocalMarketInventory $inventory, $chunkSize, $isLastChunk = false)
    {
        $this->inventory = $inventory;
        $this->chunkSize = $chunkSize;
        $this->isLastChunk = $isLastChunk;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws NeedManuallyCheckUnitsAndStatus
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            Log::info("Starting CreateLocalMarketUnitInventoriesJob for inventory ID: {$this->inventory->id}");

            $inventoryUnits = [];
            $baseName = $this->inventory->generateQrCodeBaseName();

            for ($j = 0; $j < $this->chunkSize; $j++) {
                $uuid = Uuid::uuid4()->toString();
                $inventoryUnits[] = [
                    'local_market_inventory_id' => $this->inventory->id,
                    'commodity_item_id' => $this->inventory->item->id,
                    'qr_code' => $baseName . '-' . $uuid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Log::info("Inserting {$this->chunkSize} inventory units for inventory ID: {$this->inventory->id}");
            LocalMarketInventoryUnits::insert($inventoryUnits);
            if ($this->isLastChunk) {
                $totalUnitsCreated = $this->inventory->CountOfUnits();
                $availableQuantity = $this->inventory->available_quantity;

                Log::info("Total units created: {$totalUnitsCreated}, Available quantity: {$availableQuantity}");

                // Update the inventory status based on the unit count
                if ($totalUnitsCreated == $availableQuantity) {
                    Log::info("Inventory ID: {$this->inventory->id} set to Active status");
                } elseif ($totalUnitsCreated < $availableQuantity) {
                    $missingUnits = $availableQuantity - $totalUnitsCreated;
                    Log::info("Dispatching additional job for {$missingUnits} missing units for inventory ID: {$this->inventory->id}");
                    self::dispatch($this->inventory, $missingUnits, true)->onQueue('unit-inventory');
                } else {
                    Log::error("Inventory ID: {$this->inventory->id} set to Problem status. More units created than available");
                    throw new NeedManuallyCheckUnitsAndStatus();
                }
            }

            unset($inventoryUnits);
            DB::commit();
            Log::info("Transaction committed and finished CreateLocalMarketUnitInventoriesJob for inventory ID: {$this->inventory->id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Transaction rolled back for inventory ID: {$this->inventory->id}. Error: {$e->getMessage()}");
            Log::error($e->getTraceAsString());
            throw new NeedManuallyCheckUnitsAndStatus();
        }
    }
}
