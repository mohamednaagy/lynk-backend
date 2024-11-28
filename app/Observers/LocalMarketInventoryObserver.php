<?php

namespace App\Observers;

use App\Enums\LocalMarket\InventoryStatus;
use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\LocalMarketInventory;
use App\Services\LocalMarket\LiveMarketService;
use Illuminate\Support\Facades\DB;

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
        // Generate QR codes for units
        $this->createItemUnits($inventory);

        // Update inventory stock
        UpdateInventoryStock::dispatch($inventory, $inventory->available_quantity, $inventory->wasRecentlyCreated);

        // Add to live market if active and has quantity
        $this->liveMarketService->handleNewInventory($inventory);
    }

    /**
     * Handle the LocalMarketInventory "updating" event.
     */
    public function updating(LocalMarketInventory $inventory): void
    {
        // If quantity is being updated, generate new units if needed
        if ($inventory->isDirty('available_quantity') && $inventory->available_quantity > $inventory->getOriginal('available_quantity')) {
            $additionalQuantity = $inventory->available_quantity - $inventory->getOriginal('available_quantity');
            $this->createItemUnits($inventory, $additionalQuantity);
        }
    }

    /**
     * Handle the LocalMarketInventory "updated" event.
     */
    public function updated(LocalMarketInventory $inventory): void
    {
        // Handle status changes
        if ($inventory->wasChanged('status')) {
            if ($inventory->status !== InventoryStatus::Active) {
                $this->liveMarketService->handleInventoryDeletion($inventory);
            } elseif ($inventory->status === InventoryStatus::Active && $inventory->available_quantity > 0) {
                $this->liveMarketService->handleNewInventory($inventory);
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

        if (
            $inventory->wasChanged('max_price')
        ) {
            $this->liveMarketService->handleInventoryPriceUpdate($inventory);
        }
    }

    /**
     * Handle the LocalMarketInventory "deleted" event.
     */
    public function deleted(LocalMarketInventory $inventory): void
    {
        $this->liveMarketService->handleInventoryDeletion($inventory);
    }

    /**
     * Create QR codes for inventory units
     */
    private function createItemUnits(LocalMarketInventory $inventory, ?int $quantity = null): void
    {
        $quantityToGenerate = $quantity ?? $inventory->available_quantity;

        DB::select(
            'CALL GenerateRandomInventoryUnitsQRCode(?, ?, ?, ?)',
            [
                $inventory->id,
                $inventory->commodity_item_id,
                $quantityToGenerate,
                $inventory->company_id,
            ]
        );
    }
}
