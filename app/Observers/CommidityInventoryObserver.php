<?php

namespace App\Observers;

use App\Enums\InventoryStatus;
use App\Jobs\CreateUnitInventoriesJob;
use App\Models\Inventory;
use App\Models\InventoryUnits;
use Exception;
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

    public function createItemUnits(Inventory $inventory){
        $item = $inventory->item;
        $volumeSellableUnits = (int) $item->volume_sellable_unit;
        $type = substr($item->type->unique_name, 0, 2);
        $itemId = substr($item->unique_name, 0, 2);

        $numberOfUnits = $inventory->available_quantity/$volumeSellableUnits;
        $chunkSize = 1000;
        $totalChunks = ceil($numberOfUnits / $chunkSize);
        Log::info($numberOfUnits.' starting chunk size '.$volumeSellableUnits .' - '. $totalChunks);
        for ($i = 0; $i < $totalChunks; $i++) {
            $start = $i * $chunkSize;
            $end = min(($i + 1) * $chunkSize, $numberOfUnits);
            $inventoryUnits = [];

            for ($j = $start; $j < $end; $j++) {
                $qrCode = $type . $itemId . str_pad(mt_rand(10000, 99999), 6, '0', STR_PAD_LEFT);
                Log::info($qrCode);
                $inventoryUnits[] = [
                    'inventory_id' => $inventory->id,
                    'commodity_item_id' => $inventory->item->id,
                    'qr_code' => $qrCode,
                ];
            }
            Log::info($inventoryUnits);
            InventoryUnits::insert($inventoryUnits);
        }

        $inventory->update([
            'status' => InventoryStatus::Active,
        ]);

        // CreateUnitInventoriesJob::dispatch($inventory, $type, $itemId, $volumeSellableUnits)
        // ->onQueue('unit-inventory');

        
       
    }
}
