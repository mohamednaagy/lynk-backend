<?php

namespace App\Actions\Contracts\Commodities\CommodityLocation;

use App\Models\SupplierLocation;

interface DeleteSupplierLocation
{
    public function handle(SupplierLocation $supplierLocation): SupplierLocation;
}
