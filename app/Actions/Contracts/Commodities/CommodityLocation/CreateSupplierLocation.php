<?php

namespace App\Actions\Contracts\Commodities\CommodityLocation;

use App\Models\SupplierLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CreateSupplierLocation
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(array $data): SupplierLocation;
}
