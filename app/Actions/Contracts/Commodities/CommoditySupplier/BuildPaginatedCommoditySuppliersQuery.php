<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use Illuminate\Database\Eloquent\Builder;

interface BuildPaginatedCommoditySuppliersQuery
{
    public function handle(): Builder;
}
