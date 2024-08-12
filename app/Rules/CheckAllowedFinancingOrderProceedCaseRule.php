<?php

namespace App\Rules;

use App\Enums\FinancingOrderProceedCase;
use App\Models\FinancingOrder;
use Illuminate\Contracts\Validation\Rule;

class CheckAllowedFinancingOrderProceedCaseRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    private FinancingOrder $order;

    public function __construct(FinancingOrder $order)
    {
        $this->order = $order;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $traderOrder = $this->order->activeTraderOrder()->firstOrFail();

        return in_array($value, FinancingOrderProceedCase::ALLOWED_TO_PROCEED_STATUS[$traderOrder->provider]);

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
