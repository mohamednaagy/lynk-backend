<?php

namespace App\Transformers\Admin\CommodityItem;

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
        'commodity_type',
        'min_price',
        'max_price',
        'volume_sellable_unit',
        'currency',
        'measurement',
        'available_units',
        'reserved_units',
        'created_at',
        'supplier',

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

    public function includeAvailableUnits(CommodityItem $commodityItem)
    {
        return $this->primitive($commodityItem->available_units);
    }

    public function includeReservedUnits(CommodityItem $commodityItem)
    {
        return $this->primitive($commodityItem->reserved_units);
    }

    public function includeCommodityType(CommodityItem $commodityItem): Primitive
    {
        $type = $commodityItem->type;

        return $this->primitive([
            'id' => $type->id,
            'name' => $type->name,
            'status' => [
                'value' => $type->status->value,
                'description' => $type->status->description,
            ]
        ]);
    }

    public function includeMinPrice(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive($commodityItem->min_price);
    }

    public function includeMaxPrice(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive($commodityItem->max_price);
    }

    public function includeVolumeSellableUnit(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive($commodityItem->volume_sellable_unit);
    }

    public function includeCurrency(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive([
            'id' => $commodityItem->currency_id,
            'name' => $commodityItem->currency->name,
        ]);
    }

    public function includeMeasurement(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive([
            'id' => $commodityItem->measurement_id,
            'name' => $commodityItem->measurement->name,
        ]);
    }

    public function includeSupplier(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive([
            'id' => $commodityItem->company_id,
            'name' => $commodityItem->supplier->name,
            'status' => [
                'value' => $commodityItem->supplier->detail->status->value,
                'description' => $commodityItem->supplier->detail->status->description,
            ]
        ]);
    }

    public function includeCreatedAt(CommodityItem $commodityItem): Primitive
    {
        return $this->primitive(optional($commodityItem->created_at)->format('Y-m-d'));
    }
}
