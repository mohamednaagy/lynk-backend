<?php

namespace App\Actions\Supplier\CommodityItem\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\UpdateCommodityInventory;
use App\Exceptions\InventoryNotUpdatable;
use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\LocalMarketInventory;

class UpdateCommodityInventoryAction implements UpdateCommodityInventory
{
    public function handle(LocalMarketInventory $inventory, array $data): LocalMarketInventory
    {
        if (! $inventory->canUpdateUnits($data['total_units'])) {
            throw new InventoryNotUpdatable;
        }

        if ($data['total_units']) {
            UpdateInventoryStock::dispatch($inventory, $data['total_units']);
        }

        return $inventory;
    }
}
