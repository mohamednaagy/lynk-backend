<?php

namespace App\Jobs\LocalMarket\InventoryEligibleQuantities;

use App\Models\LocalMarketInventory;
use App\Services\LocalMarket\EligibleQuantityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $inventoryId
    ) {
        $this->onQueue('local_market_eligible_quantities');
    }

    public function handle(EligibleQuantityService $service): void
    {
        try {
            $inventory = LocalMarketInventory::findOrFail($this->inventoryId);

            Log::channel('live_market')->info('Starting delete eligible quantities for inventory', [
                'inventory_id' => $this->inventoryId,
            ]);

            $service->deleteForInventory($inventory);

            Log::channel('live_market')->info('Completed delete eligible quantities for inventory', [
                'inventory_id' => $this->inventoryId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('live_market')->error('Failed to delete eligible quantities for inventory', [
                'inventory_id' => $this->inventoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
