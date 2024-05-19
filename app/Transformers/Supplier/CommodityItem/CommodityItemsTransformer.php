<?php

namespace App\Transformers\Supplier\CommodityItem;

use App\Models\CommodityItem;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class CommodityItemsTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'name',
        'unique_name',
        'description',
        'company_id',
        'commodity_type',
        'min_price',
        'max_price',
        'volume_sellable_unit',
        'currency_id',
        'measurement_id',
        'measurement_id',
        'available_units',
        'reserved_units',
        'created_at',

    ];

    public function transform(CommodityItem $commodityItem): array
    {
        return [];
    }

    public function includeId(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive($commodityItem->id);
    }

    public function includeName(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive($commodityItem->name);
    }

    public function includeUniqueName(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive($commodityItem->unique_name);
    }

    public function includeDescription(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive($commodityItem->description);
    }

    public function includeAvailableUnits()
    {
        return $this->primitive(0);
    }

    public function includeReservedUnits()
    {
        return $this->primitive(0);
    }

    public function includeCommodityType(CommodityItem $commodityItem): Primitive
    {
        $type = $commodityItem->types()->first();

        return $this->primitive([
            'id' => $type->id,
            'name' => $type->name,
        ]);
    }

    public function includeCreatedAt(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive(optional($commodityItem->created_at)->format('Y-m-d'));
    }
}
