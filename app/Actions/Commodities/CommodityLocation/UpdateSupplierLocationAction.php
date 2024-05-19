<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommodityLocation\UpdateSupplierLocation;
use App\Models\SupplierLocation;
use Illuminate\Support\Arr;

class UpdateSupplierLocationAction implements UpdateSupplierLocation
{
    public function handle(SupplierLocation $commoditySupplier, array $data): SupplierLocation
    {
        $commoditySupplier->update(
            Arr::only(
                $data,
                [
                    'unique_identifier',
                    'name',
                    'description',
                ]
            )
        );

        return $commoditySupplier;
    }
}
