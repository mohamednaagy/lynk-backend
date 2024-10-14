<?php

namespace App\Actions\Commodities\CommodityLocation;

use App\Actions\Contracts\Commodities\CommodityLocation\DeleteSupplierLocation;
use App\Jobs\LocalMarket\DeleteSupplierLocation as DeleteSupplierLocationJob;
use App\Models\SupplierLocation;

class DeleteSupplierLocationAction implements DeleteSupplierLocation
{
    public function handle(SupplierLocation $supplierLocation): SupplierLocation
    {
        DeleteSupplierLocationJob::dispatch($supplierLocation);
        return $supplierLocation;
    }
}
