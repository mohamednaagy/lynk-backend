<?php

namespace App\Observers;

use App\Models\CommodityItem;
use App\Models\Inventory;

class CommodityItemObserver
{
    public $afterCommit = true;

    /**
     * Handle the CommodityItem "updated" event.
     *
     * @return void
     */
    public function updated(CommodityItem $item)
    {        
        if ($item->isDirty('min_price') || $item->isDirty('max_price')) {
            Inventory::where('commodity_item_id', $item->id)->update([
                'min_price'         => $item->min_price,
                'max_price'         => $item->max_price,
                'commodity_type_id' => $item->commodity_type_id,
            ]);
        }
    }
}
