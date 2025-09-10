<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CompanyUniqueNameRule implements Rule
{
    protected string $errorMessage = '';

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

        if (! preg_match('/^(?!-).*$/', $value)) {
            $this->errorMessage = __('validation.no_hyphen_at_start');

            return false;
        }

        if (! preg_match('/^[a-zA-Z0-9_-]+(?:\s[a-zA-Z0-9_-]+)*$/', $value)) {
            $this->errorMessage = __('validation.only_english_alpha_numbers_underscore_hyphen_allowed');

            return false;
        }

        return true;

    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->errorMessage ?: __('validation.custom.commodity.regex');
    }
}
