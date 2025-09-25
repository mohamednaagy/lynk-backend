<?php

namespace App\Rules;

use App\Models\Lender;
use Illuminate\Contracts\Validation\Rule;

class CheckNeedToSelectFinancingOrderTypeRule implements Rule
{
    private $lenderId;

    private $value;

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
        if (count($allowedFinancingOrderTypes) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('validation.need_to_select_financing_order_type');
    }
}
