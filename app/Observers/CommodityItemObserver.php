<?php

namespace App\Observers;

use App\Models\CommodityItem;
use App\Services\LocalMarket\LiveMarketService;

class CommodityItemObserver
{
    public $afterCommit = true;

    public function __construct(private LiveMarketService $liveMarketService) {}

    /**
     * Handle the CommodityItem "updated" event.
     *
     * @return void
     */
    public function updated(CommodityItem $item)
    {

        if ($item->isDirty('commodity_type_id')) {
            $this->liveMarketService->handleCommodityItemTypeUpdate($item, $item->commodity_type_id);

        }

        if ($item->isDirty('max_price')) {
            // TODO no need to update inventories table we will rely on live market table
            $this->liveMarketService->handleCommodityItemPriceUpdate($item, $item->max_price);
        }
    }

    public function deleted(CommodityItem $item)
    {
        $this->liveMarketService->handleCommodityItemDeletion($item);
    }
}
