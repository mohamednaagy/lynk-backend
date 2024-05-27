<?php

namespace App\Actions\Commodities\CommodityLocation;

use App\Actions\Contracts\Commodities\CommodityLocation\CreateSupplierLocation;
use App\Models\SupplierLocation;
use Illuminate\Support\Arr;

class CreateSupplierLocationAction implements CreateSupplierLocation
{
    public function handle(array $data): SupplierLocation
    {
        // create location
        return SupplierLocation::create(Arr::only(
            $data,
            [
                'unique_identifier',
                'name',
                'description',
                'company_id',
            ]
        ));
    }
}
