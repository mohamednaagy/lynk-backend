<?php

namespace App\Support\DocumentEngine\Traits;

use App\Support\DataTransferObjects\CommodityProductDto;
use Illuminate\Support\Collection;

trait HasProducts
{
    public function transformProductsToCommodityProductsDTO($products): Collection
    {
        return collect($products)->map(function ($product) {
            return CommodityProductDto::fromArray([
                'product' => $product['product'],
                'quantity' => $product['quantity'],
                'uom' => $product['uom'],
                'amount' => $product['amount'],
                'warehouse' => $product['warehouse'],
                'previous_owner' => $product['previous_owner'],
                'date_time_of_purchasing_commodity' => $product['date_time_of_purchasing_commodity'],
            ]);
        });
    }

    public function transformProductsToLocalCommodityProductsDTO($products, string|array|null $groupByKeys = null): Collection
    {

        return collect($products)
            ->when($groupByKeys, function (Collection $productCollection) use ($groupByKeys) {
                $keys = (array) $groupByKeys;

                return $productCollection
                    ->groupBy(fn ($item) => $this->generateGroupKey($item, $keys))
                    ->map(fn (Collection $group) => $this->mapGroupToDto($group));
            }, function (Collection $productCollection) {
                return $productCollection->map(fn (array $product) => $this->mapProductToDto($product));
            });

    }
}
