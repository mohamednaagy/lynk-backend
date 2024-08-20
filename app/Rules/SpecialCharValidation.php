<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SpecialCharValidation implements Rule
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
        // Define the regular expression pattern to allow the specified special characters
        $pattern = '/[\/\\\\?<>|“\'.:;~,\sA-Za-z0-9]+/';


        // Check if the value matches the pattern
        return preg_match($pattern, $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute contains invalid characters.';
    }
}
