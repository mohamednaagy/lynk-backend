<?php

namespace App\Actions\Contracts\Supplier\CommodityItem\Inventory;

use App\Models\CommodityItem;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedCommodityInventories
{
    public function handle(Supplier $supplier, CommodityItem $commodityItem): LengthAwarePaginator;
}
