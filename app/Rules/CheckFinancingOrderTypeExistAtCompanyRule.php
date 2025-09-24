<?php

namespace App\Rules;

use App\Models\Lender;
use Illuminate\Contracts\Validation\Rule;

class CheckFinancingOrderTypeExistAtCompanyRule implements Rule
{
    private $lenderId;

    public function __construct($lenderId)
    {
        $this->lenderId = $lenderId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        $allowedFinancingOrderTypes = Lender::find($this->lenderId)->allowedFinancingOrderTypes();

        return in_array($value, $allowedFinancingOrderTypes);
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('validation.the_selected_financing_order_type_is_not_allowed_for_this_company');
    }
}
