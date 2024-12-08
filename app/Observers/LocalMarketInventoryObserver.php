<?php

namespace App\Observers;

use App\Enums\LocalMarket\InventoryStatus;
use App\Jobs\LocalMarket\LiveMarket\PublishInventoryToLiveMarket;
use App\Jobs\LocalMarket\LiveMarket\UpdateInventoryInLiveMarket;
use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\LocalMarketInventory;
use App\Services\LocalMarket\LiveMarketService;

class LocalMarketInventoryObserver
{
    public $afterCommit = true;

    protected LiveMarketService $liveMarketService;

    public function __construct(LiveMarketService $liveMarketService)
    {
        $this->liveMarketService = $liveMarketService;
    }

    /**
     * Handle before creating the inventory
     */
    public function creating(LocalMarketInventory $inventory): void
    {
        $inventory->status = InventoryStatus::Active();
    }

    /**
     * Handle the LocalMarketInventory "created" event.
     */
    public function created(LocalMarketInventory $inventory): void
    {
        // Update inventory stock
        UpdateInventoryStock::dispatch($inventory, $inventory->available_quantity, $inventory->wasRecentlyCreated);

        // Add to live market if active and has quantity
        // PublishInventoryToLiveMarket::dispatch($inventory);

    }

    /**
     * Handle the LocalMarketInventory "updating" event.
     */
    public function updating(LocalMarketInventory $inventory): void {}

    /**
     * Handle the LocalMarketInventory "updated" event.
     */
    public function updated(LocalMarketInventory $inventory): void
    {
        // Handle status changes
        // if ($inventory->wasChanged('status')) {
        //     if ($inventory->status->is(InventoryStatus::Active)) {
        //         PublishInventoryToLiveMarket::dispatch($inventory);
        //     } else {
        //         $this->liveMarketService->handleInventoryDeletion($inventory);
        //     }
        // }

        // if ($inventory->wasChanged('available_quantity')) {
        //     // UpdateInventoryInLiveMarket::dispatch($inventory);
        //     RebuildInventoryEligibleQuantities::dispatch($inventory->id);
        // }
    }

    /**
     * Handle the LocalMarketInventory "deleted" event.
     */
    public function deleted(LocalMarketInventory $inventory): void
    {
        // $this->liveMarketService->handleInventoryDeletion($inventory);
    }
}
