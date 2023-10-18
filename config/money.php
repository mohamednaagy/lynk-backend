<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Laravel money
     |--------------------------------------------------------------------------
     */
    'locale' => config('app.locale', 'en_US'),
    'defaultCurrency' => config('app.currency', 'SAR4'),
    'defaultFormatter' => null,
    'isoCurrenciesPath' => __DIR__.'/../vendor/moneyphp/money/resources/currency.php',
    'currencies' => [
        'iso' => 'all',
        'custom' => [
            'SAR4' => 4,
        ],
    ],
    'fixedExchange' => [
        'SAR4' => [
            'SAR' => '1',
        ],
    ],
];
