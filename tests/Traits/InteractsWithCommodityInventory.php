<?php

namespace Tests\Traits;

use App\Enums\InventoryStatus;
use App\Models\Inventory;
use App\Models\Supplier;

trait InteractsWithCommodityInventory
{
    use InteractsWithCommodityItem , InteractsWithCurrency , InteractsWithMeasurements, InteractsWithSupplier;

    public function createInventory(
        Supplier $supplier,
        int $total_units
    ): Inventory {
        $item = $this->createCommodityItem($supplier);
        $location = $this->createSupplierLocation($supplier);
        $commodity_inventory = Inventory::query()->create([
            'company_id'            => $supplier->id,
            'commodity_item_id'     => $item->id,
            'commodity_type_id'     => $item->commodity_type_id,
            'supplier_location_id'  => $location->id, 
            'min_price'             => $item->min_price,
            'max_price'             => $item->max_price,
            'reserved_items'        => 0,
            'available_quantity'    => $total_units,
            'status'                => InventoryStatus::Pending,
        ]);

        return $commodity_inventory;
    }
}
