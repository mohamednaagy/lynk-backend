<?php

namespace App\Observers;

use App\Enums\LocalMarket\InventoryStatus;
use App\Jobs\LocalMarket\CommoditiesSettlement\DispatchOrderSettlementCheck;
use App\Jobs\LocalMarket\InventoryEligibleQuantities\DeleteInventory as DeleteInventoryEligibleQuantities;
use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\LocalMarketInventory;

class LocalMarketInventoryObserver
{
    public $afterCommit = true;

    /**
     * Handle before creating the inventory
     */
    public function creating(LocalMarketInventory $inventory): void
    {
        $inventory->status = InventoryStatus::Active();
        $inventory->commodity_type_id = $inventory->item->commodity_type_id ?? null;
    }

    /**
     * Handle the LocalMarketInventory "created" event.
     */
    public function created(LocalMarketInventory $inventory): void
    {
        // Update inventory stock
        UpdateInventoryStock::dispatch($inventory->id, $inventory->available_quantity, $inventory->wasRecentlyCreated);
    }

    /**
     * Handle the LocalMarketInventory "updating" event.
     */
    public function updating(LocalMarketInventory $inventory): void {}

    /**
     * Handle the LocalMarketInventory "updated" event.
     */
    public function updated(LocalMarketInventory $inventory): void {}

    /**
     * Handle the LocalMarketInventory "deleted" event.
     */
    public function deleted(LocalMarketInventory $inventory): void
    {
        DeleteInventoryEligibleQuantities::dispatch($inventory->id);

        // Dispatch a job to verify the settlement status of inventory units.
        DispatchOrderSettlementCheck::dispatch(null, $inventory->id);
    }
}
