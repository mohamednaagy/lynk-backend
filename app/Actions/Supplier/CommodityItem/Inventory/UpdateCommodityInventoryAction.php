<?php

namespace App\Actions\Supplier\CommodityItem\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\UpdateCommodityInventory;
use App\Exceptions\InventoryNotUpdatableException;
use App\Exceptions\InventoryUpdateConflictException;
use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\LocalMarketInventory;

class UpdateCommodityInventoryAction implements UpdateCommodityInventory
{
    public function handle(LocalMarketInventory $inventory, array $data): LocalMarketInventory
    {
        if (! $inventory->is_editable) {
            throw new InventoryNotUpdatableException;
        }

        if (! $inventory->canUpdateUnits($data['total_units'])) {
            throw new InventoryUpdateConflictException;
        }

        if ($data['total_units']) {
            UpdateInventoryStock::dispatch($inventory->id, $data['total_units']);
        }

        return $inventory;
    }
}
