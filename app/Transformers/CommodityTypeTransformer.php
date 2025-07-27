<?php

namespace App\Transformers;

use App\Models\CommodityType;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class CommodityTypeTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'name',
        'unique_name',
        'status',
        'provider',
        'description',
        'total_value',
        'available_value',
        'reserved_value',
        'created_at',
    ];

    public function transform(CommodityType $commodityType): array
    {
        return [];
    }

    public function includeId(CommodityType $commodityType): Primitive
    {
        return $this->primitive($commodityType->id);
    }

    public function includeName(CommodityType $commodityType): Primitive
    {
        return $this->primitive($commodityType->name);
    }

    public function includeUniqueName(CommodityType $commodityType): Primitive
    {
        return $this->primitive($commodityType->unique_name);
    }

    public function includeDescription(CommodityType $commodityType): Primitive
    {
        return $this->primitive($commodityType->description);
    }

    public function includeProvider(CommodityType $commodityType): Primitive
    {
        return $this->primitive($commodityType->provider->value);
    }

    public function includeStatus(CommodityType $commodityType): Primitive
    {
        return $this->primitive([
            'value' => $commodityType->status->value,
            'description' => $commodityType->status->description,
        ]);
    }

    
    public function includeTotalValue(CommodityType $commodityType): Primitive
    {
        return $this->primitive($commodityType->statistics?->total_value);
    }

    
    public function includeAvailableValue(CommodityType $commodityType): Primitive
    {
        return $this->primitive($commodityType->statistics?->available_value);
    }

    
    public function includeReservedValue(CommodityType $commodityType): Primitive
    {
        return $this->primitive($commodityType->statistics?->reserved_value);
    }


    public function includeCreatedAt(CommodityType $commodityType): Primitive
    {
        return $this->primitive(optional($commodityType->created_at)->format('Y-m-d'));
    }
}
