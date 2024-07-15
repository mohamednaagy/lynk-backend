<?php

namespace App\Observers;

use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\LocalMarketInventory;
use App\Services\InventoryItemUnitsService;
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
        app(InventoryItemUnitsService::class)->createItemUnits($inventory);
    }

    /**
     * Handle the LocalMarketInventory "updated" event.
     *
     * @return void
     */
    public function updating(LocalMarketInventory $inventory)
    {
        // Log the current state for debugging
        Log::info("Before update: {$inventory->getOriginal('total_items')} - total_items: {$inventory->total_items}, isDirty: {$inventory->isDirty('total_items')}");
        $currentTotalItems = $inventory->total_items;
        $originalTotalItems = $inventory->getOriginal('total_items');
        if ($currentTotalItems !== $originalTotalItems) {
            UpdateInventoryStock::dispatch($inventory, $originalTotalItems)->onQueue('unit-inventory');
        }
    }
}
