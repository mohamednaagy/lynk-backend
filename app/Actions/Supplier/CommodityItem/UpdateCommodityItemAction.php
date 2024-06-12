<?php

namespace App\Actions\Supplier\CommodityItem;

use App\Actions\Contracts\Supplier\CommodityItem\UpdateCommodityItem;
use App\Models\CommodityItem;
use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Support\Arr;

class UpdateCommodityItemAction implements UpdateCommodityItem
{
    private $supplier;

    public function handle(CommodityItem $item, array $data): CommodityItem
    {

        $item->update(
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
                    'commodity_type_id',
                ]
            )
        );

        return $item;
    }

    public function setSupplier(Supplier|Company $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }
}
