<?php

namespace App\Actions\Contracts\Supplier\CommodityItem;

use App\Models\CommodityItem;

interface DeleteCommodityItem
{
    public function handle(CommodityItem $commodityItem): CommodityItem;
}
