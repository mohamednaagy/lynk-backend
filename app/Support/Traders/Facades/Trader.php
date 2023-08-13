<?php

namespace App\Support\Traders\Facades;

use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * @method static \App\Support\Traders\Contracts\TraderInterface driver(string $driver= null, string $version= null)
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
