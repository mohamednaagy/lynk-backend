<?php

namespace App\Observers;

use App\Jobs\LocalMarket\UpdateInventoryStock;
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
    public function updating(LocalMarketInventory $inventory)
    {
        if ($inventory->isDirty('total_items')) {
            UpdateInventoryStock::dispatch($inventory, $inventory->total_items)->onQueue('unit-inventory');
        }
    }
}
