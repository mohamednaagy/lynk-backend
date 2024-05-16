<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\CreateCommoditySupplier;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Models\Company;
use Illuminate\Support\Arr;

class CreateCommoditySupplierAction implements CreateCommoditySupplier
{
    public function handle(array $data): Company
    {
        $company['status'] = CompanyStatus::Approved();
        $company['name'] = 'sup_'.$data['legal_name'];
        $company['unique_name'] = $data['unique_name'];
        $company['type'] = CompanyType::Supplier;
        $supplier = Company::create(
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
                'legal_name',
                'unique_name',
                'description',
                'market_type',
                'status',
            ]
        ));

        return $supplier;
    }
}
