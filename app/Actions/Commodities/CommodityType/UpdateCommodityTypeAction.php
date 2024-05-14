<?php

namespace App\Actions\Commodities\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\UpdateCommodityType;
use App\Models\CommodityType;
use Illuminate\Support\Arr;

class UpdateCommodityTypeAction implements UpdateCommodityType
{
    public function handle(CommodityType $commodityType, array $data): CommodityType
    {
        $commodityType->update(
            Arr::only(
                $data,
                [
                    'name',
                    'unique_name',
                    'status',
                    'description',
                ]
            )
        );

        return $commodityType;
    }
}
