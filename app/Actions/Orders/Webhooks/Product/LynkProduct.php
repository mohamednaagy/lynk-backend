<?php

namespace App\Actions\Orders\Webhooks\Product;

use App\Models\TraderOrder;
use App\Support\DataTransferObjects\LynkCommodityProductDto;

class LynkProduct implements ProductInterface
{
    public function map(TraderOrder $traderOrder): array
    {
        return collect($traderOrder->products)->map(function ($product) {
            $productDto = LynkCommodityProductDto::fromArray($product);

            return [
                'product_description' => $productDto->getProduct(),
                'product_volume_unit' => $productDto->getUom(),
                'product_volume' => $productDto->getQuantity(),
                'product_value' => $productDto->getAmount(),
                'currency' => $productDto->getCurrency(),
            ];
        })->toArray();
    }
}
