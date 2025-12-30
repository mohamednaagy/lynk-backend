<?php

namespace App\Actions\Orders;

use App\Enums\FinancingOrderBorrowerTypeEnum;
use App\Enums\FinancingOrderLenderTypeEnum;
use App\Models\FinancingOrder;
use App\Models\Lender;
use Illuminate\Support\Arr;

class NormalLendingFinancingOrderStrategy implements FinancingOrderTypeStrategy
{
    public function create(Lender $lender, array $data): FinancingOrder
    {
        $data['lender_type'] = FinancingOrderLenderTypeEnum::NormalLending;
        $data['lender_identifier'] = $lender->id;
        $data['borrower_type'] = FinancingOrderBorrowerTypeEnum::Customer;
        $data['borrower_identifier'] = $data['customer_name'];

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
