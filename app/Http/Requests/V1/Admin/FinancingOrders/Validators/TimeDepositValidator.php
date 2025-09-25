<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

class TimeDepositValidator extends AbstractFinancingOrderTypeValidator
{
    public function getRules(): array
    {
        return [
            'national_id' => ['required', 'integer', 'digits:10', 'gt:0'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required_if:is_verification_required,true', 'string', 'phone:phone_country_code,mobile'],
            'amount' => ['required', 'numeric', 'gte:1'],
            'selling_price' => ['required', 'numeric', 'gte:amount'],
            'is_verification_required' => ['required', 'boolean'],
            'customer_name' => ['required', 'string', 'max:255'],
        ];
    }
}
