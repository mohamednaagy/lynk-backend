<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use Cknow\Money\Money;

class CalculateVatAmountAction implements CalculateVatAmount
{
    protected bool $isVatIncludedInAmount;

    protected string $vatRate;

    protected Money $amount;

    public function __construct(
        protected GetProjectSettings $getProjectSettings
    ) {
    }

    public function handle(): array
    {
        if (! isset($this->amount) || ! isset($this->isVatIncludedInAmount)) {
            throw new \Exception('Amount or isVatIncludedInAmount are not set');
        }

        $vatRate = $this->vatRate ?? $this->getProjectSettings->handle()->getVatRate();

        $vatAmount = null;

        if ($this->isVatIncludedInAmount) {
            $vatAmount = $this->amount->subtract(
                $this->amount->divide(1 + $vatRate)
            );
        } else {
            $vatAmount = $this->amount->multiply($vatRate);
        }

        return [$vatAmount, $vatRate];
    }

    public function setIsVatIncludedInAmount(bool $isIncluded): CalculateVatAmount
    {
        $this->isVatIncludedInAmount = $isIncluded;

        return $this;
    }

    public function setAmount(Money $amount): CalculateVatAmount
    {
        $this->amount = $amount;

        return $this;
    }

    public function setVatRate(string $rate): CalculateVatAmount
    {
        $this->vatRate = $rate;

        return $this;
    }
}
