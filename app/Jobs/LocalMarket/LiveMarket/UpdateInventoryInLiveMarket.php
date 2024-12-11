<?php

namespace App\Jobs\LocalMarket\LiveMarket;

use App\Models\LocalMarketInventory;
use App\Services\LocalMarket\LiveMarketService;

class UpdateInventoryInLiveMarket extends BaseLiveMarketJob
{
    protected LocalMarketInventory $inventory;

    public function __construct(LocalMarketInventory $inventory)
    {
        parent::__construct();
        $this->inventory = $inventory;
    }

    public function handle(LiveMarketService $liveMarketService): void
    {
        $this->logJobStart('Updating inventory in live market', [
            'inventory_id' => $this->inventory->id,
            'commodity_item_id' => $this->inventory->commodity_item_id,
            'supplier_id' => $this->inventory->company_id,
            'status' => $this->inventory->status->value,
        ]);

        try {
            $liveMarketService->handleInventoryUpdate($this->inventory);

            $this->logJobSuccess('Successfully updated inventory in live market', [
                'inventory_id' => $this->inventory->id,
                'commodity_item_id' => $this->inventory->commodity_item_id,
            ]);
        } catch (\Throwable $e) {
            $this->logJobError('Failed to update inventory in live market', $e);
            throw $e;
        }
    }

    protected function getFailedJobContext(): array
    {
        return [
            'inventory_id' => $this->inventory->id,
            'commodity_item_id' => $this->inventory->commodity_item_id,
            'supplier_id' => $this->inventory->company_id,
            'status' => $this->inventory->status->value,
            'quantity' => $this->inventory->quantity,
            'price' => $this->inventory->item?->max_price,
            'updated_at' => $this->inventory->updated_at,
        ];
    }
}
