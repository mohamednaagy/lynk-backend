<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CommodityItemUniqueNameRole implements Rule
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
     */
    public function passes($attribute, $value): bool
    {
        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_\s]*$/', $value);

    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('validation.custom.commodity.regex');
    }
}
