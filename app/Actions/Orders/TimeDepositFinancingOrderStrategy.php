<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Enums\CompanyType;
use App\Enums\FinancingOrderBorrowerTypeEnum;
use App\Enums\FinancingOrderLenderTypeEnum;
use App\Models\FinancingOrder;
use App\Models\Lender;
use Illuminate\Support\Arr;

class TimeDepositFinancingOrderStrategy implements FinancingOrderTypeStrategy
{
    public function __construct(
        private CreateCompany $createCompany
    ) {}

    public function create(Lender $lender, array $data): FinancingOrder
    {
        $companyData['name'] = $data['customer_name'];
        $newTimeDespoistCompany = $this->createCompany->handle($companyData, CompanyType::TimeDeposit);

        $data['lender_type'] = FinancingOrderLenderTypeEnum::TimeDeposit;
        $data['lender_identifier'] = $newTimeDespoistCompany->id;
        $data['borrower_type'] = FinancingOrderBorrowerTypeEnum::Lender;
        $data['borrower_identifier'] = $lender->id;

        return $lender->orders()->create(
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
