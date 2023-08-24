<?php

namespace App\Actions\Contracts\Companies;

use Cknow\Money\Money;

interface CalculateVatAmount
{
    public function handle(): array;

    public function setIsVatIncludedInAmount(bool $isIncluded): self;

    public function setAmount(Money $amount): self;

    public function setVatRate(string $vatRate): self;
}
