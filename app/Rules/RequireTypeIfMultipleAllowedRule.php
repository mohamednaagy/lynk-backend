<?php

namespace App\Rules;

use App\Models\Lender;
use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\Rule;

class RequireTypeIfMultipleAllowedRule implements ImplicitRule, Rule
{
    public function __construct(private Lender $lender) {}

    public function passes($attribute, $value): bool
    {
        $allowed = $this->lender->allowedFinancingOrderTypes();

        // Determine count safely whether it's an array or collection-like
        $count = is_array($allowed)
            ? count($allowed)
            : (is_countable($allowed) ? count($allowed) : 0);

        // When multiple types are allowed, an explicit 'type' must be provided
        if ($count > 1 && ($value === null || $value === '')) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return __('validation.required', ['attribute' => 'type']);
    }
}
