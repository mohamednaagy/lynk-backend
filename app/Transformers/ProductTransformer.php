<?php

namespace App\Transformers;

use App\Support\DataTransferObjects\CommodityProductDto;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class ProductTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'name',
        'quantity',
        'amount',
        'currency',
    ];

    protected array $availableIncludes = [];

    public function transform(CommodityProductDto $productDto): array
    {
        return [];
    }

    public function includeName(CommodityProductDto $productDto): Primitive
    {
        return $this->primitive($productDto->getProduct());
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
}
