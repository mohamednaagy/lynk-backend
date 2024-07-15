<?php

namespace App\Observers;

use App\Jobs\UpdateInventoryStock;
use App\Models\LocalMarketInventory;
use App\Services\InventoryItemUnitsService;

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
        app(InventoryItemUnitsService::class)->createItemUnits($inventory);
    }

    /**
     * Handle the LocalMarketInventory "updated" event.
     *
     * @return void
     */
    public function updated(LocalMarketInventory $inventory)
    {
    }

    public function updating(LocalMarketInventory $inventory)
    {
        // TODO make sure total_items is dirty

        if ($this->isDirty('total_items')) {
            UpdateInventoryStock::dispatch($inventory, $inventory->total_items)->onQueue('unit-inventory');
        }

        // $originalTotalItems = $inventory->getOriginal('total_items');
        // $newTotalItems = $inventory->total_items;

        // if ($newTotalItems > $originalTotalItems) {
        //     $newUnits = $newTotalItems - $originalTotalItems;
        //     app(InventoryItemUnitsService::class)->createItemUnits($inventory, $newUnits);
        // } elseif ($newTotalItems < $originalTotalItems) {
        //     $unitsToRemove = $originalTotalItems - $newTotalItems;
        //     if ($unitsToRemove > 0) {
        //         app(InventoryItemUnitsService::class)->decreaseItemUnits($inventory, $unitsToRemove);
        //     }
        // }
    }
}
