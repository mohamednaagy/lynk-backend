<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class OrderCostTiersRangeRule implements Rule
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

    public function passes($attribute, $tiers): bool
    {
        $tiersCount = count($tiers);
        // ($tiersNumber-1) to ignore last tier
        for ($i = 0; $i < ($tiersCount - 1); $i++) {
            $currentTier = $tiers[$i];
            $nextTier = $tiers[$i + 1];
            $tiersDiff = number_format($nextTier['order_value_start'] - $currentTier['order_value_end'], 2);
            if ($tiersDiff != 0.01) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return __('validation.custom.order_cost_amount_tiers_range');
    }
}
