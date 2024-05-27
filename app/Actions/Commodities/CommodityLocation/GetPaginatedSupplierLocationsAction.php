<?php

namespace App\Actions\Commodities\CommodityLocation;

use App\Actions\Contracts\Commodities\CommodityLocation\GetPaginatedSupplierLocations;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedSupplierLocationsAction implements GetPaginatedSupplierLocations
{
    public function handle(Supplier $supplier): LengthAwarePaginator
    {
        return $supplier->locations()
            ->paginate();
    }
}
