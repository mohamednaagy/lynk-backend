<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Enums\CompanyType;
use App\Enums\FinancingOrderBorrowerTypeEnum;
use App\Enums\FinancingOrderLenderTypeEnum;
use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Support\Arr;

class SpecialPurposeVehicleFinancingOrderStrategy implements FinancingOrderTypeStrategy
{
    public function __construct(
        private CreateCompany $createCompany
    ) {}

    public function create(Company $company, array $data): FinancingOrder
    {
        $companyData['name'] = $data['customer_name'];
        $newSpvCompany = $this->createCompany->handle($companyData, CompanyType::SpecialPurposeVehicle);

        $data['lender_type'] = FinancingOrderLenderTypeEnum::SpecialPurposeVehicle;
        $data['lender_identifier'] = $newSpvCompany->id;
        $data['borrower_type'] = FinancingOrderBorrowerTypeEnum::Lender;
        $data['borrower_identifier'] = $company->id;

        return $company->orders()->create(
            Arr::only($data, [
                'borrower_identifier',
                'reference_number',
                'national_id',
                'contract_number',
                'phone_number',
                'amount',
                'selling_price',
                'currency',
                'status',
                'creator_id',
                'approved_at',
                'is_verification_required',
                'commodity_type_id',
                'type',
                'lender_type',
                'lender_identifier',
                'borrower_type',
                'borrower_identifier',
            ])
        );
    }
}
