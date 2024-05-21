<?php

namespace Tests\Traits;

use App\Models\CommodityItem;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

trait InteractsWithCommodityItem
{
    use InteractsWithCommodityType , InteractsWithCurrency , InteractsWithMeasurements;

    public function getCommodityItems(Supplier $supplier, $number_of_objects = 5, $is_paginate = false): LengthAwarePaginator|Collection
    {
        for ($i = 0; $i < $number_of_objects; $i++) {
            $this->createCommodityItem($supplier, 'item'.$i, 'unqiue_name_'.$i);
        }

        $items = CommodityItem::query()->where('company_id', $supplier->id);
        if ($is_paginate) {
            return $items->paginate();
        }

        return $items->get();

    }

    public function createCommodityItem(
        Supplier $supplier,
        ?string $name = null,
        ?string $unique_name = null,
        ?string $description = null,
        float $min_price = 10,
        float $max_price = 20,
        int $volume_sellable_unit = 10
    ): Model|Builder {

        $commodity_type = $this->createCommodityType('test', 'test_type');
        $currency = $this->createCurrency();
        $measurement = $this->createMeasurement();

        $commodity_item = CommodityItem::query()->create([
            'name' => $name ?? 'name'.rand(11, 999),
            'unique_name' => $unique_name ?? 'unique name'.rand(111, 999),
            'description' => $description ?? 'Test Description',
            'company_id' => $supplier->id,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'volume_sellable_unit' => $volume_sellable_unit,
            'currency_id' => $currency->id,
            'measurement_id' => $measurement->id,
        ]);
        $commodity_item->types()->attach($commodity_type->id);

        return $commodity_item;
    }
}
