<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class WhiteListRule implements Rule
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

        return in_array(parse_url($value)['host'], config('whitelist.hosts'));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'this Url do not match our records.';
    }
}
