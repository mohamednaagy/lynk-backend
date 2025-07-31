<?php

namespace App\Rules;

use App\Enums\FinancingOrderProceedCase;
use App\Models\FinancingOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CheckAllowedFinancingOrderProceedCaseRule implements Rule
{
    public function __construct(private FinancingOrder $financingOrder) {}

    private ?string $errorMessage = null;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $traderOrder = $this->financingOrder->activeTraderOrder()->first();
        if (! $traderOrder) {
            Log::info("No active trader order found for FinancingOrder: {$this->financingOrder->id} ,Company: {$this->financingOrder->company_id}", [
                'user_id' => auth()->user()?->id,
            ]);

            $this->errorMessage = __('error.order_has_no_active_trade_request');

            return false;
        }

        $provider = $traderOrder->provider;
        $version = $traderOrder->version;
        $value = FinancingOrderProceedCase::getKeyByDescription($value);

        if (! in_array($value, FinancingOrderProceedCase::ALLOWED_TO_PROCEED_STATUS[$provider][$version])) {
            $this->errorMessage = __('validation.attributes.invalid_case_proceed');

            return false;
        }

        if (app(TraderOrderProceedCaseService::class)->checkIfTraderHasCase($traderOrder->id, $value)) {
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
