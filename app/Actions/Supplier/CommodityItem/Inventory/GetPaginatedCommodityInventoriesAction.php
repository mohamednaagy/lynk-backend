<?php

namespace App\Actions\Supplier\CommodityItem\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\GetPaginatedCommodityInventories;
use App\Models\LocalMarketInventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedCommodityInventoriesAction implements GetPaginatedCommodityInventories
{
    public function handle($supplier, $item): LengthAwarePaginator
    {
        return LocalMarketInventory::query()
            ->where('company_id', $supplier->id)
            ->where('commodity_item_id', $item->id)
            ->paginate();
    }
}
