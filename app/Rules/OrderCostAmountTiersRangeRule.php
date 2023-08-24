<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

class OrderCostAmountTiersRangeRule implements InvokableRule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function __invoke($attribute, $tiers, $fail): void
    {
        $tiersNumber = count($tiers);
        // ($tiersNumber-1) to ignore last tier
        for ($i = 0; $i < ($tiersNumber - 1); $i++) {
            $currentTier = $tiers[$i];
            $nextTier = $tiers[$i + 1];
            $tiersDiff = number_format($nextTier['order_value_start'] - $currentTier['order_value_end'], 2);
            if ($tiersDiff > 0.01 || $tiersDiff <= 0.00) {
                //                $fail(__(":order_value_start must be greater than :order_value_end of previous tier with only 0.01",[
                //                    'order_value_start'
                //                ]));
                $fail(__('validation.custom.order_cost_amount_tiers_range'));
            }
        }
    }

    public function message()
    {
        return __('validation.custom.order_cost_amount_tiers_range');
    }
}
