<?php

namespace App\Actions\Contracts\Commodities\CommodityLocation;

use App\Models\SupplierLocation;

interface UpdateSupplierLocation
{
    public function handle(SupplierLocation $commoditySupplier, array $data): SupplierLocation;
}
