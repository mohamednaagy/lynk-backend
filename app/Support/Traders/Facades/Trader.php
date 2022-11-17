<?php

namespace App\Support\Traders\Facades;

use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * @method static driver(string $driver)
 */
class Trader extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'trader';
    }
}
