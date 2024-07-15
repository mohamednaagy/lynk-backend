<?php

namespace App\Jobs;

use App\Enums\LocalMarketInventoryStatus;
use App\Exceptions\NeedManuallyCheckUnitsAndStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
     */
    public function handle()
    {
        $inventoryUnits = [];
        $baseName = $this->inventory->generateQrCodeBaseName();
        for ($j = 0; $j < $this->chunkSize; $j++) {
            $uuid = Uuid::uuid4()->toString();
            $inventoryUnits[] = [
                'local_market_inventory_id' => $this->inventory->id,
                'commodity_item_id' => $this->inventory->item->id,
                'qr_code' => $baseName.'-'.$uuid,
                'created_at'=> now(),
                'updated_at' => now(),
            ];
        }

        LocalMarketInventoryUnits::insert($inventoryUnits);

        if ($this->isLastChunk) {
            $totalUnitsCreated = $this->inventory->CountOfUnits();
            $availableQuantity = $this->inventory->available_quantity;

            // Update the inventory status to active if the unit count matches
            if ($totalUnitsCreated == $availableQuantity) {
                $this->inventory->update([
                    'status' => LocalMarketInventoryStatus::Active,
                ]);
            } elseif ($totalUnitsCreated < $availableQuantity) {
                $missingUnits = $availableQuantity - $totalUnitsCreated;
                CreateLocalMarketUnitInventoriesJob::dispatch($this->inventory, $missingUnits, true)->onQueue('unit-inventory');
            } else {
                // throw exception
                // change inventory status to be having a problem status
                $this->inventory->update([
                    'status' => LocalMarketInventoryStatus::Problem,
                ]);

                throw new NeedManuallyCheckUnitsAndStatus();
            }
        }

        unset($inventoryUnits);
    }
}
