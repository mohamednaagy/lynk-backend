<?php

namespace App\Actions\Supplier\CommodityItem\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\UpdateCommodityInventory;
use App\Enums\LocalMarket\InventoryStatus;
use App\Models\LocalMarketInventory;

class UpdateCommodityInventoryAction implements UpdateCommodityInventory
{
    public function handle(LocalMarketInventory $inventory, array $data): LocalMarketInventory
    {
        $inventory->update([
            'available_quantity' => $data['total_units'],
            'status' => InventoryStatus::Pending,
        ]
        );

        return $inventory;
    }
}
