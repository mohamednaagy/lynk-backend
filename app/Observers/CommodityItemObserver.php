<?php

namespace App\Observers;

use App\Models\CommodityItem;
use App\Models\LocalMarketInventory;
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
        if ($item->isDirty('min_price')) {
            LocalMarketInventory::where('commodity_item_id', $item->id)->update([
                'min_price' => $item->min_price,
            ]);
        }

        if ($item->isDirty('commodity_type_id')) {
            LocalMarketInventory::where('commodity_item_id', $item->id)->update([
                'commodity_type_id' => $item->commodity_type_id,
            ]);
            $this->liveMarketService->handleCommodityItemTypeUpdate($item, $item->commodity_type_id);

        }

        if ($item->isDirty('max_price')) {
            // TODO no need to update inventories table we will rely on live market table
            LocalMarketInventory::where('commodity_item_id', $item->id)->update([
                'max_price' => $item->max_price,
            ]);
            $this->liveMarketService->handleCommodityItemPriceUpdate($item, $item->max_price);
        }
    }
}
