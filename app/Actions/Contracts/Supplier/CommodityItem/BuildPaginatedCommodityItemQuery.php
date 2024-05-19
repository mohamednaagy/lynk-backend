<?php

namespace App\Actions\Contracts\Supplier\CommodityItem;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BuildPaginatedCommodityItemQuery
{
    public function handle(Supplier|Company $supplier): LengthAwarePaginator;
}
