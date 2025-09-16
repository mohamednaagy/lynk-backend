<?php

namespace App\Http\Requests\V1\Lender\Orders\Validators;

use App\Enums\FinancingOrderTypeEnum;
use App\Rules\ValidCommodityTypeAtOrderRule;
use BenSampo\Enum\Rules\EnumValue;

class NormalLendingValidator extends AbstractFinancialProductValidator
{
    public function __construct(private int $lenderId)
    {
        parent::__construct($lenderId);
    }

    public function getRules(): array
    {
        return [
            'reference_number' => ['nullable', 'string', 'max:100', $this->handleUniqueReferenceNumber()],
            'commodity_type_id' => ['nullable', 'numeric' , new ValidCommodityTypeAtOrderRule($this->lenderId)],
            'customer_name' => ['required', 'string', 'max:255'],
            'national_id' => ['required', 'integer', 'digits:10', 'gt:0'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required_if:is_verification_required,true', 'string', 'phone:phone_country_code,mobile'],
            'amount' => ['required', 'numeric', 'gte:1'],
            'selling_price' => ['required', 'numeric', 'gte:amount'],
            'is_verification_required' => ['required', 'boolean'],
            'type' => ['nullable', 'integer',new EnumValue(FinancingOrderTypeEnum::class, false)],
        ];
    }

}
    
