<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CheckAutoVerifiedUsersIsAllowedRule implements Rule
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
        if (config('app.auto_verified_users')) {
            return true;
        }

        return false;

    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('validation.auto_verified_not_allowed');
    }
}
