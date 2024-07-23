<?php

namespace App\Rules;

use App\Enums\CommodityTypeStatus;
use App\Models\CommodityType;
use Illuminate\Contracts\Validation\Rule;

class CheckActiveCommodityTypeRule implements Rule
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

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return CommodityType::where('id', $value)
            ->where('status', CommodityTypeStatus::Active)
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The selected :attribute is invalid or inactive.';
    }
}
