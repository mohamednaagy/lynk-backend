<?php

namespace App\Transformers;

use App\Models\CommoditySupplier;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class CommoditySuppliersTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'legal_name',
        'unique_name',
        'description',
        'status',
        'market_type',
        'created_at',

    ];

    public function transform(CommoditySupplier $commoditySupplier): array
    {
        return [];
    }

    public function includeId(CommoditySupplier $commoditySupplier): Primitive
    {
        return $this->primitive($commoditySupplier->id);
    }

    public function includeLegalName(CommoditySupplier $commoditySupplier): Primitive
    {
        return $this->primitive($commoditySupplier->legal_name);
    }

    public function includeDescription(CommoditySupplier $commoditySupplier): Primitive
    {
        return $this->primitive($commoditySupplier->description);
    }

    public function includeUniqueName(CommoditySupplier $commoditySupplier): Primitive
    {
        return $this->primitive($commoditySupplier->unique_name);
    }

    public function includeStatus(CommoditySupplier $commoditySupplier): Primitive
    {
        return $this->primitive([
            'value' => $commoditySupplier->status->value,
            'description' => $commoditySupplier->status->description,
        ]);
    }

    public function includeMarketType(CommoditySupplier $commoditySupplier): Primitive
    {
        return $this->primitive([
            'value' => $commoditySupplier->market_type->value,
            'description' => $commoditySupplier->market_type->description,
        ]);
    }

    public function includeCreatedAt(CommoditySupplier $commoditySupplier): Primitive
    {
        return $this->primitive(optional($commoditySupplier->created_at)->format('Y-m-d'));
    }
}
