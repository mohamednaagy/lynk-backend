<?php

namespace App\Rules;

use App\Enums\FinancingOrderProceedCase;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CheckAllowedFinancingOrderProceedCaseRule implements Rule
{
    public function __construct(private TraderOrder $traderOrder) {}

    private string $errorMessage;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $provider = $this->traderOrder->provider;
        $version = $this->traderOrder->version;
        $value = FinancingOrderProceedCase::getKeyByDescription($value);

        if (! in_array($value, FinancingOrderProceedCase::ALLOWED_TO_PROCEED_STATUS[$provider][$version])) {
            $this->errorMessage = __('validation.attributes.invalid_case_proceed');

            return false;
        }

        if (app(TraderOrderProceedCaseService::class)->checkIfTraderHasCase($this->traderOrder->id, $value)) {
            Log::info("traderOrderId already proceed this $value before");
            $this->errorMessage = __('error.order_status_doesnt_follow_sequence');

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
        return $this->errorMessage;
    }
}
