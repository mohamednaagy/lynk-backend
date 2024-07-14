<?php

namespace App\Actions\Supplier\CommodityItem\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\UpdateCommodityInventory;
use App\Enums\LocalMarketInventoryStatus;
use App\Models\LocalMarketInventory;

class UpdateCommodityInventoryAction implements UpdateCommodityInventory
{
    public function handle(LocalMarketInventory $inventory, array $data): LocalMarketInventory
    {
        $inventory->update([
            'available_quantity' => $data['total_units'] - $inventory->reserved_items,
            'status' => LocalMarketInventoryStatus::Pending,
        ]
        );

        return $inventory;
    }
}
