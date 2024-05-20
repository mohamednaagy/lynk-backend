<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\UpdateCommoditySupplier;
use App\Models\Supplier;
use Illuminate\Support\Arr;

class UpdateCommoditySupplierAction implements UpdateCommoditySupplier
{
    public function handle(Supplier $supplier, array $data): Supplier
    {
        $supplier->commoditySupplier->update(
            Arr::only(
                $data,
                [
                    'description',
                    'market_type',
                    'status',
                ]
            )
        );
        $supplier->update([
            'name' => $data['legal_name'],
            'unique_name' => $data['unique_name'],
        ]);

        return $supplier;
    }
}
