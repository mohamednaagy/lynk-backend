<?php

namespace App\Actions\Commodities\CommodityLocation;

use App\Actions\Contracts\Commodities\CommodityLocation\UpdateCommodityInventory;
use App\Enums\InventoryStatus;
use App\Models\Inventory;
use App\Models\Supplier;
use App\Models\SupplierLocation;

class UpdateCommodityInventoryAction implements UpdateCommodityInventory
{
    public function handle(Inventory $inventory, array $data): Inventory
    {
        $inventory->update([
            'available_quantity'    => $data['total_units'],
            'status'                => InventoryStatus::Pending,
        ]
        );

        return $inventory;
    }
}
