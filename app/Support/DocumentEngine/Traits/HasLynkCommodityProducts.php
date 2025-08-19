<?php

namespace App\Support\DocumentEngine\Traits;

use App\Support\DataTransferObjects\LynkCommodityProductDto;
use Illuminate\Support\Collection;

trait HasLynkCommodityProducts
{
    public function transformProductsToLynkCommodityProductsDTO($products, string|array|null $groupByKeys = null): Collection
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

    private function mapProductToDto(array $product): LynkCommodityProductDto
    {
        return LynkCommodityProductDto::fromArray($product);
    }

    private function generateGroupKey(array $item, array $keys): string
    {
        return implode('|', array_map(fn (string $key) => $item[$key] ?? '', $keys));
    }

    private function mapGroupToDto(Collection $group): LynkCommodityProductDto
    {
        $firstItem = $group->first();

        return LynkCommodityProductDto::fromArray([
            'product' => $firstItem['product'],
            'type' => $firstItem['type'],
            'quantity' => $group->sum('quantity'),
            'uom' => $firstItem['uom'],
            'amount' => $group->sum('amount'),
            'location' => $firstItem['location'],
            'currency' => $firstItem['currency'],
            'original_supplier' => $firstItem['original_supplier'],
            'previous_owner' => $firstItem['previous_owner'],
        ]);
    }
}
