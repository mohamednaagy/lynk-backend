<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ExcludeCommodityTypeId implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Check if commodity_type_id is passed in the request
        return ! request()->has('commodity_type_id');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The commodity_type_id should not be passed in the request.';
    }
}
