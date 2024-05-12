<?php

namespace App\Actions\Contracts\Commodities\CommodityType;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BuildPaginatedCommodityTypeQuery
{
    public function handle(): LengthAwarePaginator;
}
