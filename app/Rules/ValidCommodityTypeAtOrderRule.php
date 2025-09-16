<?php

namespace App\Rules;

use App\Enums\CommodityTypeStatus;
use App\Models\Company;
use App\Models\CompanyLenderDetail;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCommodityTypeAtOrderRule implements ValidationRule
{
    private Company $company;
    private CompanyLenderDetail $lenderDetail;
    public function __construct(
        private int $companyId
    ) {
        $this->company = Company::find($this->companyId);
        $this->lenderDetail = $this->company->lender->lenderDetail;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value) || $value === '') {
            return;
        }

       
        $allowCommoditySelection = $company->lender?->lenderDetail?->allow_preferred_commodity_in_order ?? false;

        if (! $allowCommoditySelection) {
            $fail('Order not created. Commodity type selection is not allowed for this company.');
            return;
        }

        $exists = $this->company->lenderOrderAllowedCommodityTypes()
            ->where('commodity_types.id', $value)
            ->where('commodity_types.status', CommodityTypeStatus::Active)
            ->exists();

        if (! $exists) {
            $fail("The selected commodity type is not allowed for this company.");
        }
    }
}
