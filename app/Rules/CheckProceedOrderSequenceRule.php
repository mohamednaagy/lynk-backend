<?php

namespace App\Rules;

use App\Enums\FinancingOrderProceedCase;
use App\Factories\TraderOrders\TraderOrderProceedCaseFactory;
use App\Models\FinancingOrder;
use Illuminate\Contracts\Validation\Rule;

class CheckProceedOrderSequenceRule implements Rule
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
            $this->errorMessage = __('error.order_has_no_active_trade_request');

            return false;
        }
        $value = FinancingOrderProceedCase::getKeyByDescription($value);
        $proceedCaseHandler = TraderOrderProceedCaseFactory::handle($value);
        $canProceed = $proceedCaseHandler->canProceed($traderOrder, false);

        if ($canProceed) {
            return true;
        }
        $this->errorMessage = __('error.order_status_doesnt_follow_sequence', [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $traderOrder->id,
        ]);

        return false;
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
