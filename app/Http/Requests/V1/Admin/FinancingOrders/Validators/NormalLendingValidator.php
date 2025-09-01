<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

use App\Enums\FinancingProductEnum;
use App\Models\Company;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Validation\Rule;

class NormalLendingValidator implements FinancingProductValidatorInterface
{
        public function getRules(array $baseRules): array
        {
            return array_merge($baseRules, [
                'company_id' => ['required', Rule::exists(Company::class, 'id')],
                'financing_product_id' => ['nullable', 'integer', new EnumValue(FinancingProductEnum::class, false)],
                'national_id' => ['required', 'integer', 'digits:10', 'gt:0'],
                'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
                'phone_number' => ['required_if:is_verification_required,true', 'string', 'phone:phone_country_code,mobile'],
                'amount' => ['required', 'numeric', 'gte:1'],
                'selling_price' => ['required', 'numeric', 'gte:amount'],
                'is_verification_required' => ['required', 'boolean'],
                'customer_name' => ['required', 'string', 'max:255'],
            ]);
        }

        public function getMessages(): array
        {
            return [];
        }
    
        public function getAttributes(): array
        {
            return [];
        }
}
    
