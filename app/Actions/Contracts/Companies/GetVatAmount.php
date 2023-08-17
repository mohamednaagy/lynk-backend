<?php

namespace App\Actions\Contracts\Companies;

use App\Support\Money\Money;

interface GetVatAmount
{
    public function handle(Money $amount): array;
}
