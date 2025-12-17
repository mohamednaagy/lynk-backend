<?php

namespace App\Validators;

use App\Enums\Trader;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProviderValidator implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $providers = explode(',', $value);
        foreach ($providers as $provider) {
            $provider = trim($provider);
            if (! Trader::hasValue($provider)) {
                $fail('The :attribute contains invalid provider value.');

                return;
            }
        }
    }
}
