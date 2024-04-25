<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildPaginatedCommoditySuppliersQuery;
use App\Models\CommoditySupplier;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCommoditySuppliersQueryAction implements BuildPaginatedCommoditySuppliersQuery
{
    public function handle(): Builder
    {
        return CommoditySupplier::query();
    }
}
