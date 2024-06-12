<?php

namespace App\Observers;

use App\Jobs\CreateLocalMarketUnitInventoriesJob;
use App\Models\LocalMarketInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        DB::transaction(function () use ($inventory) {
            try {
                $numberOfUnits = $inventory->available_quantity;
                $chunkSize = ($numberOfUnits <= 20000) ? $numberOfUnits : 20000;
                $totalChunks = ceil($numberOfUnits / $chunkSize); // Use ceil to ensure covering all units
                //setup the remaining units
                $remainingUnits = $numberOfUnits;
                //loop through the chunks
                for ($i = 0; $i < $totalChunks; $i++) {
                    // Get if this is the last chunk
                    $is_last_chunk = ($i == $totalChunks-1);
                    //chk if is last chunk then calculate remaining chunk size
                    if ($is_last_chunk)
                        $chunkSize = ($remainingUnits > $chunkSize) ? $chunkSize : $remainingUnits;
                    //dispatch job
                    CreateLocalMarketUnitInventoriesJob::dispatch($inventory, $chunkSize, $is_last_chunk)->onQueue('unit-inventory');
                    //decrease remaining units
                    $remainingUnits = $numberOfUnits - $chunkSize;
                }
            } catch (\Exception $e) {
                DB::rollBack();
            }
        });
    }
}
