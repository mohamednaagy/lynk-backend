<?php

namespace App\Traits;

use App\Enums\TransactionReason;

trait AdjustsFinancingOrderCostsTrait
{
    public function updateFinancingOrderCosts(int $reason, float $costWithVat, float $costWithoutVat): void
    {

        if (! TransactionReason::hasValue($reason)) {
            throw new \InvalidArgumentException('Invalid transaction reason');
        }

        if (TransactionReason::shouldIncreaseTransactionAmount($reason)) {
            $this->addToFinancingOrderCosts($costWithVat, $costWithoutVat);

            return;
        }
        if (TransactionReason::shouldDecreaseTransactionAmount($reason)) {
            $this->subtractFromFinancingOrderCosts($costWithVat, $costWithoutVat);

            return;
        }
    }

    public function addToFinancingOrderCosts(float $costWithVat, float $costWithoutVat): void
    {
        $this->cost_with_vat += $costWithVat;
        $this->cost_without_vat += $costWithoutVat;
        $this->save();
    }

    public function subtractFromFinancingOrderCosts(float $costWithVat, float $costWithoutVat): void
    {
        $this->cost_with_vat -= $costWithVat;
        $this->cost_without_vat -= $costWithoutVat;
        $this->save();
    }
}
