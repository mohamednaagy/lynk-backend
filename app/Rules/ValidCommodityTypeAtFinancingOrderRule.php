<?php

namespace App\Rules;

use App\Enums\CommodityTypeStatus;
use App\Models\Lender;
use Illuminate\Contracts\Validation\Rule;

class ValidCommodityTypeAtFinancingOrderRule implements Rule
{
    private Lender $lender;

    protected string $errorMessage = '';


    public function __construct(
        private int $lenderId
    ) {
        $this->lender = Lender::find($this->lenderId);
    }


    public function passes($attribute, $value): bool
    {
       
        if (! $this->lender->isPreferredCommoditySelectionAllowed()) {
            $this->errorMessage = __('validation.order_not_created_commodity_type_selection_not_allowed');
            return false;
        }

        $exists = $this->lender->lenderOrderAllowedCommodityTypes()
            ->where('commodity_types.id', $value)
            ->where('commodity_types.status', CommodityTypeStatus::Active)
            ->exists();

        if (! $exists) {
            $this->errorMessage = __('validation.the_selected_commodity_type_is_not_allowed_for_this_company');
            return false;
        }

        return true;

    }


    public function message(): string
    {
        return $this->errorMessage ;
    }
}
