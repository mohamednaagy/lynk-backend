<?php

namespace App\Actions\Contracts\Commodities\CommodityItem;

use App\Models\CommodityItem;

interface DeleteCommodityItem
{
    public function handle(CommodityItem $commodityItem): CommodityItem;
}
