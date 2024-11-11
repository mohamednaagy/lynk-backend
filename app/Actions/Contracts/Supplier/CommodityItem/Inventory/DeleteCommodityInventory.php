<?php

namespace App\Actions\Contracts\Supplier\CommodityItem\Inventory;

use App\Models\LocalMarketInventory;

interface DeleteCommodityInventory
{
    public function handle(LocalMarketInventory $inventory): LocalMarketInventory;
}
