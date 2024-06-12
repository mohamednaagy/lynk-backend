<?php

namespace App\Observers;

use App\Exceptions\ErrorCreatingUnitsForThisINventory;
use App\Jobs\CreateLocalMarketUnitInventoriesJob;
use App\Models\LocalMarketInventory;
use Exception;

class LocalMarketInventoryObserver
{
    public $afterCommit = true;

    /**
     * Handle the LocalMarketInventory "created" event.
     *
     * @return void
     */
    public function created(LocalMarketInventory $inventory)
    {
        $this->createItemUnits($inventory);
    }

    /**
     * Handle the LocalMarketInventory "updated" event.
     *
     * @return void
     */
    public function updated(LocalMarketInventory $inventory)
    {
    }

    public function createItemUnits(LocalMarketInventory $inventory)
    {
        try {
            $numberOfUnits = $inventory->available_quantity;
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
}
