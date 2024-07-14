<?php

namespace App\Services;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Exceptions\ErrorCreatingUnitsForThisINventory;
use App\Jobs\CreateLocalMarketUnitInventoriesJob;
use App\Jobs\DecreaseInventoryUnitsJob;
use App\Models\LocalMarketInventory;
use Illuminate\Support\Facades\DB;

class InventoryItemUnitsService
{

    public function createItemUnits(LocalMarketInventory $inventory, $total_units = null)
    {
        try {
            $numberOfUnits = $total_units ?? $inventory->available_quantity;
            $chunkSize = ($numberOfUnits <= 20000) ? $numberOfUnits : 20000;
            $numberOfChunks = ceil($numberOfUnits / $chunkSize); // Use ceil to ensure covering all units

            //loop through the chunks
            for ($i = 0; $i < $numberOfChunks; $i++) {
                $isLastChunk = ($i == $numberOfChunks - 1);
                if ($isLastChunk) { // Get if this is the last chunk
                    $chunkSize = $numberOfUnits - ($i * $chunkSize);
                }

                //dispatch job
                CreateLocalMarketUnitInventoriesJob::dispatch($inventory, $chunkSize, $isLastChunk)->onQueue('unit-inventory');
            }
        } catch (\Exception $e) {
            // TODO create a custom exception for inventory unit creation
            throw new ErrorCreatingUnitsForThisINventory();
        }
    }


    public function decreaseItemUnits(LocalMarketInventory $inventory, $decreased_amount)
    {
        try {
            $numberOfUnits = $decreased_amount;
            $chunkSize = ($numberOfUnits <= 20000) ? $numberOfUnits : 20000;
            $numberOfChunks = ceil($numberOfUnits / $chunkSize); // Use ceil to ensure covering all units

            //loop through the chunks
            for ($i = 0; $i < $numberOfChunks; $i++) {
                $isLastChunk = ($i == $numberOfChunks - 1);
                if ($isLastChunk) { // Get if this is the last chunk
                    $chunkSize = $numberOfUnits - ($i * $chunkSize);
                }

                //decrease units job
                DecreaseInventoryUnitsJob::dispatch($inventory, $chunkSize, $isLastChunk)->onQueue('unit-inventory');
            }
        } catch (\Exception $e) {
            // TODO create a custom exception for inventory unit creation
            throw new ErrorCreatingUnitsForThisINventory();
        }
    }
}
