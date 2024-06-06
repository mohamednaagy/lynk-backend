<?php

namespace App\Observers;

use App\Jobs\CreateUnitInventoriesJob;
use App\Models\Inventory;

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
        $item = $inventory->item;
        $volumeSellableUnits = (int) $item->volume_sellable_unit;
        $type = substr($item->type->unique_name, 0, 2);
        $itemId = substr($item->unique_name, 0, 2);
        CreateUnitInventoriesJob::dispatch($inventory, $type, $itemId, $volumeSellableUnits)->onQueue('unit-inventory');

    }
}
