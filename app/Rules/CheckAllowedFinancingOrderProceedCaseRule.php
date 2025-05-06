<?php

namespace App\Rules;

use App\Enums\FinancingOrderProceedCase;
use App\Models\TraderOrder;
use Illuminate\Contracts\Validation\Rule;

class CheckAllowedFinancingOrderProceedCaseRule implements Rule
{
    public function __construct(private TraderOrder $traderOrder)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $provider = $this->traderOrder->provider;
        $version = $this->traderOrder->version;

        return in_array($value, FinancingOrderProceedCase::ALLOWED_TO_PROCEED_STATUS[$provider][$version]);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.attributes.invalid_case_proceed');
    }
}
