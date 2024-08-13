<?php

namespace App\Transformers;

use App\Models\LocalMarketInventory;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class LocalMarketInventoryTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'company_id',
        'company_name',
        'commodity_item_id',
        'commodity_item',
        'commodity_type',
        'min_price',
        'max_price',
        'supplier_location_id',
        'supplier_location',
        'total_items',
        'available_quantity',
        'reserved_items',
        'status',
        'is_editable',

    ];

    public function transform(LocalMarketInventory $inventory): array
    {
        return [];
    }

    public function includeId(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->id);
    }

    public function includeCommodityItemId(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->commodity_item_id);
    }

    public function includeCommodityTypeId(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->commodity_type_id);
    }

    public function includeCompanyId(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->company_id);
    }

    public function includeCompanyName(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->supplier->name);
    }

    public function includeSupplierLocationId(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->supplier_location_id);
    }

    public function includeAvailableQuantity(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->available_quantity);
    }

    public function includeReservedItems(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->reserved_items);
    }

    public function includeStatus(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive([
            'value' => $inventory->status->value,
            'description' => $inventory->status->description,
        ]);

    }

    public function includeCreatedAt(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive(optional($inventory->created_at)->format('Y-m-d'));
    }

    public function includeMinPrice(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->min_price);
    }

    public function includeMaxPrice(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->max_price);
    }

    public function includeTotalItems(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->total_items);
    }

    public function includeCommodityType(LocalMarketInventory $inventory): Primitive
    {
        $type = $inventory->type;

        return $this->primitive([
            'id' => $type->id,
            'name' => $type->name,
        ]);
    }

    public function includeCommodityItem(LocalMarketInventory $inventory): Primitive
    {
        $item = $inventory->item;

        return $this->primitive([
            'id' => $item->id,
            'name' => $item->name,
        ]);
    }

    public function includeSupplierLocation(LocalMarketInventory $inventory): Primitive
    {
        $location = $inventory->location;

        return $this->primitive([
            'id' => $location->id,
            'unique_identifier' => $location->unique_identifier,
            'name' => $location->name,
        ]);
    }

    public function includeIsEditable(LocalMarketInventory $inventory): Primitive
    {
        return $this->primitive($inventory->is_editable);
    }
}
