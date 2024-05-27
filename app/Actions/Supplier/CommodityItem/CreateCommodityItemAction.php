<?php

namespace App\Actions\Supplier\CommodityItem;

use App\Actions\Contracts\Supplier\CommodityItem\CreateCommodityItem;
use App\Models\CommodityItem;
use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Support\Arr;

class CreateCommodityItemAction implements CreateCommodityItem
{
    private $supplier;

    public function handle(array $data): CommodityItem
    {
        $data['company_id'] = $this->supplier->id;
        $item = CommodityItem::create(
            Arr::only(
                $data,
                [
                    'name',
                    'unique_name',
                    'description',
                    'company_id',
                    'min_price',
                    'max_price',
                    'volume_sellable_unit',
                    'currency_id',
                    'measurement_id',
                ]
            )
        );

        $item->types()->attach($data['commodity_type_id']);

        return $item;
    }

    public function setSupplier(Supplier|Company $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }
}
