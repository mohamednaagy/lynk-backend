<?php

namespace Tests\Traits;

use App\Enums\InventoryStatus;
use App\Models\CommodityItem;
use App\Models\Inventory;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

trait InteractsWithCommodityInventory
{
    use InteractsWithCommodityItem , InteractsWithCurrency , InteractsWithMeasurements, InteractsWithSupplier;

    public function getCommodityInventories(
        Supplier $supplier,
        CommodityItem $commodityItem,
        $number_of_objects = 5,
        $is_paginate = false
    ): LengthAwarePaginator|Collection {
        for ($i = 0; $i < $number_of_objects; $i++) {
            $this->createInventory($supplier, rand(100, 200));
        }

        $inventories = Inventory::query()->where('company_id', $supplier->id)->where('commodity_item_id', $commodityItem->id);
        if ($is_paginate) {
            return $inventories->paginate();
        }

        return $inventories->get();

    }

    public function createInventory(
        Supplier $supplier,
        int $total_units
    ): Inventory {
        $item = $this->createCommodityItem($supplier);
        $location = $this->createSupplierLocation($supplier);
        $commodity_inventory = Inventory::query()->create([
            'company_id' => $supplier->id,
            'commodity_item_id' => $item->id,
            'commodity_type_id' => $item->commodity_type_id,
            'supplier_location_id' => $location->id,
            'min_price' => $item->min_price,
            'max_price' => $item->max_price,
            'reserved_items' => 0,
            'available_quantity' => $total_units,
            'status' => InventoryStatus::Pending,
        ]);

        return $commodity_inventory;
    }
}
