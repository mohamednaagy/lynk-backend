<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

class verifyPermissionStructure implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if (! (array_key_exists('subject', $value) && array_key_exists('actions', $value))) {
            $fail('The :attribute array structure not valid.');
        }
    }
}
