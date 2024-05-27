<?php

namespace App\Actions\Contracts\Commodities\CommodityLocation;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedSupplierLocations
{
    public function handle(Supplier $supplier): LengthAwarePaginator;
}
