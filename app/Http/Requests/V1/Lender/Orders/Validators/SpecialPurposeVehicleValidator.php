<?php

namespace App\Http\Requests\V1\Lender\Orders\Validators;

use App\Enums\FinancingOrderTypeEnum;
use App\Models\Company;
use App\Rules\ValidCommodityTypeAtFinancingOrderRule;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Validation\Rule;

class SpecialPurposeVehicleValidator extends AbstractFinancingOrderTypeValidator
{


    public function getRules(): array
    {
        return [
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
    
