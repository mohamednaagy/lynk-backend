<?php

namespace App\Rules;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Enums\WalletType;
use App\Models\Company;
use Cknow\Money\Money;
use Illuminate\Contracts\Validation\Rule;

class IsAmountMultiplesOfOrderCost implements Rule
{
    protected Money $orderCostWithVat;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(protected Company $company)
    {
        [$vatAmount] = app(CalculateVatAmount::class)->setAmount($this->company->order_cost)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        $this->orderCostWithVat = $this->company->order_cost->add($vatAmount);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $totalAmountWithVat = Money::parseByDecimal(
            $value,
            $this->company->getWallet(WalletType::CompanyWallet)->currency
        );

        return $totalAmountWithVat->mod($this->orderCostWithVat)->getAmount() === '0';
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.amount_not_multiples_of_order_cost', [
            'order_cost_with_vat' => $this->orderCostWithVat->formatByDecimal(),
        ]);
    }
}
