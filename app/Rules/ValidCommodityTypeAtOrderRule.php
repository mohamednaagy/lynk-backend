<?php

namespace App\Rules;

use App\Enums\CommodityTypeStatus;
use App\Models\Lender;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function PHPUnit\Framework\isEmpty;

class ValidCommodityTypeAtOrderRule implements ValidationRule
{
    private Lender $lender;
    public function __construct(
        private int $lenderId
    ) {
        $this->lender = Lender::find($this->lenderId);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value) ) {
            return;
        }

       
        if (! $this->lender->isPreferredCommoditySelectionAllowed()) {
            $fail('Order not created. Commodity type selection is not allowed for this company.');
            return;
        }

        $exists = $this->lender->lenderOrderAllowedCommodityTypes()
            ->where('commodity_types.id', $value)
            ->where('commodity_types.status', CommodityTypeStatus::Active)
            ->exists();

        if (! $exists) {
            $fail("The selected commodity type is not allowed for this company.");
        }
    }
}
