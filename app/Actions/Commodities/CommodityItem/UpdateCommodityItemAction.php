<?php

namespace App\Actions\Commodities\CommodityItem;

use App\Actions\Contracts\Commodities\CommodityItem\UpdateCommodityItem;
use App\Models\CommodityItem;
use Illuminate\Support\Arr;

class UpdateCommodityItemAction implements UpdateCommodityItem
{

    public function handle(CommodityItem $item, array $data): CommodityItem
    {
        if (isset($data['commodity_supplier_id'])) {
            $data['company_id'] = $data['commodity_supplier_id'];
        }

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
                ]
            )
        );

        return $item;
    }
}
