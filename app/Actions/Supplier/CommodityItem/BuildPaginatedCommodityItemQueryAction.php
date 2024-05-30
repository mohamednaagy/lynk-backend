<?php

namespace App\Actions\Supplier\CommodityItem;

use App\Actions\Contracts\Supplier\CommodityItem\BuildPaginatedCommodityItemQuery;
use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildPaginatedCommodityItemQueryAction implements BuildPaginatedCommodityItemQuery
{
    public function handle(Supplier|Company $supplier): LengthAwarePaginator
    {
        return $supplier->commodityItems()
            ->paginate();
    }
}
