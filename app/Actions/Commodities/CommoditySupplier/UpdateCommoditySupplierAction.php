<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\UpdateCommoditySupplier;
use App\Models\CommoditySupplier;
use Illuminate\Support\Arr;

class UpdateCommoditySupplierAction implements UpdateCommoditySupplier
{
    public function handle(CommoditySupplier $commoditySupplier, array $data): CommoditySupplier
    {
        $commoditySupplier->update(
            Arr::only(
                $data,
                [
                    'legal_name',
                    'description',
                    'unique_name',
                    'market_type',
                    'status',
                ]
            )
        );
        $commoditySupplier->company()->update([
            'name' => $data['legal_name'],
            'unique_name' => $data['unique_name'],
        ]);

        return $commoditySupplier;
    }
}
