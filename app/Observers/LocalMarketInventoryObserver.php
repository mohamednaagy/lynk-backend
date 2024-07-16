<?php

namespace App\Observers;

use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\LocalMarketInventory;

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
        UpdateInventoryStock::dispatch($inventory, $inventory->available_quantity, $inventory->wasRecentlyCreated);
    }

    /**
     * Handle the LocalMarketInventory "updated" event.
     *
     * @return void
     */
    public function updating(LocalMarketInventory $inventory)
    {

    }
}
