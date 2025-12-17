<?php

namespace App\Validators;

use App\Enums\CommodityTypeProvider;
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

        $invalidProvider = collect(explode(',', $value))
            ->map(fn ($provider) => trim($provider))
            ->filter()
            ->first(fn ($provider) => ! CommodityTypeProvider::hasValue($provider));

        if ($invalidProvider !== null) {
            $fail("The :attribute contains an invalid provider value: '{$invalidProvider}'.");
        }
    }
}
