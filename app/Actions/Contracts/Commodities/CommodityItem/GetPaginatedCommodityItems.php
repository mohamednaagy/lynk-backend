<?php

namespace App\Actions\Contracts\Commodities\CommodityItem;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedCommodityItems
{
    public function handle(): LengthAwarePaginator;
}
