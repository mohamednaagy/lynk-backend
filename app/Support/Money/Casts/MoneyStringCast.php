<?php

namespace App\Support\Money\Casts;

use Cknow\Money\Casts\MoneyCast;
use Cknow\Money\Money;

class MoneyStringCast extends MoneyCast
{
    /**
     * Get formatter.
     *
     * @param  \Cknow\Money\Money  $money
     * @return string
     */
    protected function getFormatter(Money $money)
    {
        return $money->getAmount();
    }
}
