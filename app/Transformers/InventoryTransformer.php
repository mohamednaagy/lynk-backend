<?php

namespace App\Transformers;

use App\Models\Inventory;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class InventoryTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'company_id',
        'comapny_name',
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

    public function transform(Inventory $inventory): array
    {
        return [];
    }

    public function includeId(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->id);
    }

    public function includeCommodityItemId(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->commodity_item_id);
    }

    public function includeCommodityTypeId(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->commodity_type_id);
    }

    public function includeCompanyId(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->company_id);
    }

    public function includeComapnyName(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->supplier->name);
    }

    public function includeSupplierLocationId(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->supplier_location_id);
    }

    public function includeAvailableQuantity(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->available_quantity);
    }

    public function includeReservedItems(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->reserved_items);
    }

    public function includeStatus(Inventory $inventory): Primitive
    {
        return $this->primitive([
            'value' => $inventory->status->value,
            'description' => $inventory->status->description,
        ]);
        
    }

    public function includeCreatedAt(Inventory $inventory): Primitive
    {
        return $this->primitive(optional($inventory->created_at)->format('Y-m-d'));
    }

    public function includeMinPrice(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->min_price);
    }

    public function includeMaxPrice(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->max_price);
    }

    public function includeTotalItems(Inventory $inventory): Primitive
    {
        return $this->primitive($inventory->total_items);
    }

    public function includeCommodityType(Inventory $inventory): Primitive
    {
        $type = $inventory->type;

        return $this->primitive([
            'id' => $type->id,
            'name' => $type->name,
        ]);
    }

    public function includeCommodityItem(Inventory $inventory): Primitive
    {
        $item = $inventory->item;
        return $this->primitive([
            'id' => $item->id,
            'name' => $item->name,
        ]);
    }

    public function includeSupplierLocation(Inventory $inventory): Primitive
    {
        $location = $inventory->location;
        return $this->primitive([
            'id' => $location->id,
            'unique_identifier' => $location->unique_identifier,
            'name' => $location->name,
        ]);
    }

    public function includeIsEditable(Inventory $inventory): Primitive
    {
        return $this->primitive([
            'value' => $inventory->reserved_units == 0 ? 1: 0,
            'description' => $inventory->reserved_units == 0 ? 'Active': 'Inactive',
        ]);
    }
    
}
