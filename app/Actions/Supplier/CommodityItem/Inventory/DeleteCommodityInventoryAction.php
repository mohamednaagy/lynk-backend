<?php

namespace App\Actions\Supplier\CommodityItem\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\DeleteCommodityInventory;
use App\Jobs\LocalMarket\DeleteInventoryStock;
use App\Models\LocalMarketInventory;

class DeleteCommodityInventoryAction implements DeleteCommodityInventory
{
    public function handle(LocalMarketInventory $inventory): LocalMarketInventory
    {
        DeleteInventoryStock::dispatch($inventory);

        return $inventory;
    }
}
