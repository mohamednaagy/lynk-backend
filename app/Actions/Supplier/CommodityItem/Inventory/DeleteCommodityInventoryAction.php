<?php

namespace App\Actions\Supplier\CommodityItem\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\DeleteCommodityInventory;
use App\Jobs\LocalMarket\DeleteInventory;
use App\Models\LocalMarketInventory;

class DeleteCommodityInventoryAction implements DeleteCommodityInventory
{
    public function handle(LocalMarketInventory $inventory): LocalMarketInventory
    {
        DeleteInventory::dispatch($inventory);

        return $inventory;
    }
}
