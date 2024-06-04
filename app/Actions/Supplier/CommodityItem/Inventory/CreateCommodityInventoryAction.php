<?php

namespace App\Actions\Supplier\CommodityItem\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\CreateCommodityInventory;
use App\Enums\InventoryStatus;
use App\Models\CommodityItem;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\Supplier;
use Illuminate\Support\Arr;

class CreateCommodityInventoryAction implements CreateCommodityInventory
{
    private $supplier;
    private $item;

    public function handle(array $data): Inventory
    {
        $item = Inventory::create(
            [
                'company_id'            => $this->supplier->id,
                'commodity_item_id'     => $this->item->id,
                'commodity_type_id'     => $this->item->commodity_type_id,
                'supplier_location_id'  => $data['location_id'], 
                'min_price'             => $this->item->min_price,
                'max_price'             => $this->item->max_price,
                'reserved_items'        => 0,
                'available_quantity'    => $data['total_units'],
                'status'                => InventoryStatus::Pending,
            ]
        );

        return $item;
    }

    public function setSupplier(Supplier|Company $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function setItem(CommodityItem $item): static
    {
        $this->item = $item;

        return $this;
    }
}
