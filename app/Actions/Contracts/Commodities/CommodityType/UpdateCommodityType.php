<?php

namespace App\Actions\Contracts\Commodities\CommodityType;

use App\Models\CommodityType;

interface UpdateCommodityType
{
    public function handle(CommodityType $commodityType, array $data): CommodityType;
}
