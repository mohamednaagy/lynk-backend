<?php

namespace App\Actions\Commodities\CommodityItem;

use App\Actions\Contracts\Commodities\CommodityItem\DeleteCommodityItem;
use App\Jobs\LocalMarket\DeleteCommodityItem as DeleteCommodityItemJob;
use App\Models\CommodityItem;

class DeleteCommodityItemAction implements DeleteCommodityItem
{
    public function handle(CommodityItem $item): CommodityItem
    {
        DeleteCommodityItemJob::dispatch($item);

        return $item;
    }
}
