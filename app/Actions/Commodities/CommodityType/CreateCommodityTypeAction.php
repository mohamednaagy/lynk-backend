<?php

namespace App\Actions\Commodities\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\CreateCommodityType;
use App\Models\CommodityType;
use Illuminate\Support\Arr;

class CreateCommodityTypeAction implements CreateCommodityType
{
    public function handle(array $data): CommodityType
    {
        return CommodityType::create(
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
    }
}
