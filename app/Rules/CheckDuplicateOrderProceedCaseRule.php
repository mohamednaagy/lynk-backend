<?php

namespace App\Rules;

use App\Enums\FinancingOrderProceedCase;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CheckDuplicateOrderProceedCaseRule implements Rule
{
    public function __construct(private TraderOrder $traderOrder) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (app(TraderOrderProceedCaseService::class)->checkIfTraderHasCase($this->traderOrder->id, FinancingOrderProceedCase::getKeyByDescription($value))) {
            Log::info("traderOrderId already proceed this $value before");

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('error.order_status_doesnt_follow_sequence');
    }
}
