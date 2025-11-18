<?php

namespace App\Rules;

use App\Enums\CommodityTypeStatus;
use App\Models\Lender;
use Illuminate\Contracts\Validation\Rule;

class ValidCommodityTypeAtFinancingOrderForLenderRule implements Rule
{
    protected string $errorMessage = '';

    public function __construct(
        private Lender $lender
    ) {}

    public function passes($attribute, $value): bool
    {

        if (empty($value)) {
            return false;
        }

        if (! $this->lender->isPreferredCommoditySelectionAllowed()) {
            $this->errorMessage = __('validation.order_not_created_commodity_type_selection_not_allowed');

            return false;
        }

        $commodityTypeExists = $this->lender->lenderOrderAllowedCommodityTypes()
            ->where('commodity_types.unique_name', $value)
            ->where('commodity_types.status', CommodityTypeStatus::Active)
            ->exists();

        if ($commodityTypeExists) {
            return true;
        } else {
            $this->errorMessage = __('validation.the_selected_commodity_type_is_not_allowed_for_this_company');

            return false;
        }

    }

    public function message(): string
    {
        return $this->errorMessage;
    }
}
