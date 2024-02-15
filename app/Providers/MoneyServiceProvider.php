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
        Money::macro('convertAndFormatByDecimal', function (string $currency = 'SAR', ?string $sperator = null) {
            $exchange = new FixedExchange(config('money.fixedExchange'));
            $converter = new Converter(Money::getCurrencies(), $exchange);
            $result = $converter->convert($this->getMoney(), new Currency($currency));
            $money = Money::convert($result)->formatByDecimal();

            return $sperator
                ? number_format($money, 2, '.', $sperator)
                : $money;
        });

        Money::macro('convertToDisplayableCurrency', function (?string $currency = null) {
            $exchange = new FixedExchange(config('money.fixedExchange'));
            $converter = new Converter(Money::getCurrencies(), $exchange);
            if ($currency === null) {
                $currency = config(
                    'money.displayableCurrenciesMap.'.$this->getCurrency(),
                    $currency
                );
            }
            $result = $converter->convert($this->getMoney(), new Currency($currency));

            return Money::convert($result);
        });

        Money::macro('convertToCurrency', function (?string $currency) {
            $exchange = new FixedExchange(config('money.fixedExchange'));
            $converter = new Converter(Money::getCurrencies(), $exchange);
            $result = $converter->convert($this->getMoney(), new Currency($currency));

            return Money::convert($result);
        });
    }
}
