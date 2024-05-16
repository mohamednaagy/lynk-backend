<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedSupplierUsers
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator;

    public function setSupplier(Company $supplier);
}
