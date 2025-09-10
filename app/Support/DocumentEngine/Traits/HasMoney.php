<?php

namespace App\Support\DocumentEngine\Traits;

use Cknow\Money\Money;

trait HasMoney
{
    public function getAmount(): Money
    {
        return $this->context['amount'] ?? null;
    }
}
