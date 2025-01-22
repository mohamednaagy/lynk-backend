<?php

namespace App\Actions\Contracts\Commodities\CommodityItem;

use App\Models\CommodityItem;

interface UpdateCommodityItem
{
    public function handle(CommodityItem $commodityItem, array $data): CommodityItem;
}
