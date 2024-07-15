<?php

namespace App\Observers;

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
        //TODO: Handle the LocalMarketInventory "updated" event
        //DROP OLD CREATED UNITS FROM LocalMarketInventoryUnits
        //$this->createItemUnits($inventory);
    }

    public function updating(LocalMarketInventory $inventory)
    {
        $originalTotalItems = $inventory->getOriginal('total_items');
        $newTotalItems = $inventory->total_items;

        if ($newTotalItems > $originalTotalItems) {
            $newUnits = $newTotalItems - $originalTotalItems;
            app(InventoryItemUnitsService::class)->createItemUnits($inventory, $newUnits);
        } elseif ($newTotalItems < $originalTotalItems) {
            $unitsToRemove = $originalTotalItems - $newTotalItems;
            if ($unitsToRemove > 0) {
                app(InventoryItemUnitsService::class)->decreaseItemUnits($inventory, $unitsToRemove);
            }
        }
    }
}
