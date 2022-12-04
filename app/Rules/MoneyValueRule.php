<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MoneyValueRule implements Rule
{
    /**
     * @param  int  $decimal
     * @return void
     */
    public function __construct(protected ?int $decimal = 2)
    {
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
        $regex = '/^[1-9]+(\.\d{1,'.$this->decimal.'})?$/';

        return preg_match($regex, $value) > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.regex');
    }
}
