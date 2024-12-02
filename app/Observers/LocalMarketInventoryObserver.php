<?php

namespace App\Observers;

use App\Enums\LocalMarket\InventoryStatus;
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
        $this->liveMarketService->handleNewInventory($inventory);
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
        if ($inventory->wasChanged('status')) {
            if ($inventory->status->is(InventoryStatus::Active)) {
                $this->liveMarketService->handleNewInventory($inventory);
            } else {
                $this->liveMarketService->handleInventoryDeletion($inventory);
            }
        }

        // Handle quantity changes
        if ($inventory->wasChanged('available_quantity')) {
            if ($inventory->available_quantity <= 0) {
                $this->liveMarketService->handleInventoryDeletion($inventory);
            } else {
                $this->liveMarketService->handleInventoryUpdate($inventory);
            }

            // Dispatch stock update job
            UpdateInventoryStock::dispatch(
                $inventory,
                $inventory->available_quantity - $inventory->getOriginal('available_quantity'),
                false
            );
        }
    }

    /**
     * Handle the LocalMarketInventory "deleted" event.
     */
    public function deleted(LocalMarketInventory $inventory): void
    {
        $this->liveMarketService->handleInventoryDeletion($inventory);
    }
}
