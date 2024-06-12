<?php

namespace App\Actions\Contracts\Supplier\CommodityItem\Inventory;

use App\Models\LocalMarketInventory;

interface UpdateCommodityInventory
{
    public function handle(LocalMarketInventory $inventory, array $data): LocalMarketInventory;
}
