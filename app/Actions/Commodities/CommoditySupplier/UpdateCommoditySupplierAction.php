<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\UpdateCommoditySupplier;
use App\Models\Company;
use Illuminate\Support\Arr;

class UpdateCommoditySupplierAction implements UpdateCommoditySupplier
{
    public function handle(Company $company, array $data): Company
    {
        $company->commoditySupplier->update(
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
        $company->update([
            'name' => $data['legal_name'],
            'unique_name' => $data['unique_name'],
        ]);

        return $company;
    }
}
