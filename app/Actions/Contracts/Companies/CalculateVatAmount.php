<?php

namespace App\Actions\Contracts\Companies;

use App\Support\Money\Money;

interface CalculateVatAmount
{
    public function handle(Money $amount): array;
}
