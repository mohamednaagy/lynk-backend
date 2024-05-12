<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\CreateCommoditySupplier;
use App\Models\CommoditySupplier;
use Illuminate\Support\Arr;

class CreateCommoditySupplierAction implements CreateCommoditySupplier
{
    public function handle(array $data): CommoditySupplier
    {
        return CommoditySupplier::create(
            Arr::only(
                $data,
                [
                    'legal_name',
                    'description',
                    'unique_name',
                    'status',
                    'market_type',
                ]
            )
        );
    }
}
