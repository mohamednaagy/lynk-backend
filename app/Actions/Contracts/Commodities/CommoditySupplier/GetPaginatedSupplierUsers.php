<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;


use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedSupplierUsers
{
    public function handle(Company $supplier): LengthAwarePaginator;
    // public function setSupplier(Company $supplier);
}
