<?php

namespace App\Actions\Commodities\CommodityItem;

use App\Actions\Contracts\Commodities\CommodityItem\GetPaginatedCommodityItems;
use App\Models\CommodityItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedCommodityItemsAction implements GetPaginatedCommodityItems
{

    public function handle(): LengthAwarePaginator
    {
        return CommodityItem::paginate();
    }
}
