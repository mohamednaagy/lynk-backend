<?php

namespace App\Actions\Commodities\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Models\CommodityType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildPaginatedCommodityTypeQueryAction implements BuildPaginatedCommodityTypeQuery
{
    public function handle(): LengthAwarePaginator
    {
        return CommodityType::query()->paginate();
    }
}
