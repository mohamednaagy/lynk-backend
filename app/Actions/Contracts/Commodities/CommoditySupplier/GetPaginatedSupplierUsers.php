<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\CommoditySupplier;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedSupplierUsers
{
    public function handle(Company $company): LengthAwarePaginator;

    //public function setSupplier(CommoditySupplier $supplier);
}
