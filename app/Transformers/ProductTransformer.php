<?php

namespace App\Transformers;

use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class ProductTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
    ];

    protected array $availableIncludes = [
        'name',
        'volume_unit',
        'quantity',
        'amount',
        'currency',
        'type',
    ];

    public function transform(CommodityProductDto $productDto): array
    {
        return [];
    }

    public function includeName(CommodityProductDto $productDto): Primitive
    {
        return $this->primitive($productDto->getProduct());
    }

    public function includeVolumeUnit(CommodityProductDto $productDto): Primitive
    {
        return $this->primitive($productDto->getUom());
    }

    public function includeQuantity(CommodityProductDto $productDto): Primitive
    {
        return $this->primitive($productDto->getQuantity());
    }

    public function includeAmount(CommodityProductDto $productDto): Primitive
    {
        return $this->primitive($productDto->getAmount());
    }

    public function includeCurrency(CommodityProductDto $productDto): Primitive
    {
        return $this->primitive($productDto->getCurrency());
    }

    public function includeType(CommodityProductDto $productDto): Primitive
    {
        if ($productDto instanceof LynkCommodityProductDto) {
            return $this->primitive($productDto->getType());
        }

        return $this->primitive(null);

    }
}
