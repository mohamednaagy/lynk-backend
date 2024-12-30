<?php

namespace App\Actions\Commodities\CommodityItem;

use App\Actions\Contracts\Commodities\CommodityItem\CreateCommodityItem;
use App\Models\CommodityItem;
use Illuminate\Support\Arr;

class CreateCommodityItemAction implements CreateCommodityItem
{
    public function handle(array $data): CommodityItem
    {
        $data['company_id'] = $data['commodity_supplier_id'];
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
                    'commodity_type_id',
                ]
            )
        );

        return $item;
    }
}
