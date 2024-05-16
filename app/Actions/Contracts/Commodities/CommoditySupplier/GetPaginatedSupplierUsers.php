<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\CommoditySupplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedSupplierUsers
{
    public function handle(): LengthAwarePaginator;

    public function setSupplier(CommoditySupplier $supplier);
}
