<?php

namespace App\Support\Traders\Facades;

use App\Models\Company;
use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * @method static \App\Support\Traders\Contracts\TraderInterface driver(string $driver= null, string $version= null)
 * @method static \App\Support\Traders\Contracts\TraderInterface getSuitableDriverForCompany(Company $company)
 */
class Trader extends Facade
{
    /**
     * Get the registered name of the component.
     *
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'trader';
    }
}
