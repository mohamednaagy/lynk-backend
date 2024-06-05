<?php

namespace App\Actions\Contracts\Commodities\CommodityLocation;

use App\Models\Inventory;

interface UpdateCommodityInventory
{
    public function handle(Inventory $inventory, array $data): Inventory;
}
