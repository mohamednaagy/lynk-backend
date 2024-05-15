<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\CommoditySupplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedSupplierUsers
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(CommoditySupplier $supplier): LengthAwarePaginator;

    //public function setSupplier(CommoditySupplier $supplier);
}
