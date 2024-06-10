<?php

namespace App\Observers;

use App\Jobs\CreateUnitInventoriesJob;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommidityInventoryObserver
{
    public $afterCommit = true;

    /**
     * Handle the Inventory "created" event.
     *
     * @return void
     */
    public function created(Inventory $inventory)
    {
        $this->createItemUnits($inventory);
    }

    /**
     * Handle the Inventory "updated" event.
     *
     * @return void
     */
    public function updated(Inventory $inventory)
    {
    }

    public function createItemUnits(Inventory $inventory)
    {
        DB::transaction(function () use ($inventory) {
            try {
                $numberOfUnits = $inventory->available_quantity;
                $chunkSize = ($numberOfUnits <= 20000) ? $numberOfUnits : 20000;
                $totalChunks = ceil($numberOfUnits / $chunkSize); // Use ceil to ensure covering all units
                for ($i = 0; $i < $totalChunks; $i++) {
                    $is_last_chunk = ($i == $totalChunks-1);
                    CreateUnitInventoriesJob::dispatch($inventory, $chunkSize, $is_last_chunk)->onQueue('unit-inventory');
                }
            } catch (\Exception $e) {
                DB::rollBack();
            }
        });
    }
}
