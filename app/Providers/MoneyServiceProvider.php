<?php

namespace App\Providers;

use Cknow\Money\Money;
use Illuminate\Support\ServiceProvider;
use Money\Converter;
use Money\Currency;
use Money\Exchange\FixedExchange;

class MoneyServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        Money::macro('convertAndFormatByDecimal', function (string $currency = 'SAR') {
            $exchange = new FixedExchange(config('money.fixedExchange'));
            $converter = new Converter(Money::getCurrencies(), $exchange);
            $result = $converter->convert($this->getMoney(), new Currency($currency));

            return Money::convert($result)->formatByDecimal();
        });
    }
}
