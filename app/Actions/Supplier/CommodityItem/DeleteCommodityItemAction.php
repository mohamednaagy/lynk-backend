<?php

namespace App\Actions\Supplier\CommodityItem;

use App\Actions\Contracts\Supplier\CommodityItem\DeleteCommodityItem;
use App\Jobs\LocalMarket\DeleteCommodityItem as DeleteCommodityItemJob;
use App\Models\CommodityItem;

class DeleteCommodityItemAction implements DeleteCommodityItem
{
    public function handle(CommodityItem $commodityItem): CommodityItem
    {
        DeleteCommodityItemJob::dispatch($commodityItem);
        return $commodityItem;
    }
}
