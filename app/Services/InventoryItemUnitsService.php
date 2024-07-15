<?php

namespace App\Services;

use App\Exceptions\ErrorCreatingUnitsForThisINventory;
use App\Exceptions\FailedDecreaseUnitsForInventory;
use App\Jobs\LocalMarket\CreateLocalMarketUnitInventoriesJob;
use App\Jobs\LocalMarket\DecreaseInventoryUnitsJob;
use App\Models\LocalMarketInventory;
use Illuminate\Support\Facades\DB;

class InventoryItemUnitsService
{
    public function createItemUnits(LocalMarketInventory $inventory, $total_units = null)
    {
        try {
            // TODO no need to recalculate numberOfUnits
            $numberOfUnits = $total_units ?? $inventory->available_quantity;
            $chunkSize = ($numberOfUnits <= 10000) ? $numberOfUnits : 10000;
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
            DB::beginTransaction();
            DecreaseInventoryUnitsJob::dispatch($inventory, $decreased_amount)->onQueue('unit-inventory');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // TODO create a custom exception for inventory unit creation
            throw new FailedDecreaseUnitsForInventory();
        }
    }
}
