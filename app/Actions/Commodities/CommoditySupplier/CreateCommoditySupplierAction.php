<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\CreateCommoditySupplier;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Models\Supplier;
use Illuminate\Support\Arr;

class CreateCommoditySupplierAction implements CreateCommoditySupplier
{
    public function handle(array $data): Supplier
    {
        $company['status'] = CompanyStatus::Approved();
        $company['name'] = $data['legal_name'];
        $company['unique_name'] = $data['unique_name'];
        $company['type'] = CompanyType::Supplier;
        $supplier = Supplier::create(
            Arr::only(
                $company,
                [
                    'name',
                    'unique_name',
                    'status',
                    'type',
                ]
            )
        );
        $supplier->commoditySupplier()->create(Arr::only(
            $data,
            [
                'description',
                'market_type',
                'status',
            ]
        ));

        return $supplier;
    }
}
