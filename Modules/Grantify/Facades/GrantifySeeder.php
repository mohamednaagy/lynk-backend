<?php

namespace Modules\Grantify\Facades;

use RuntimeException;
use Illuminate\Support\Facades\Facade;

/**
 * @method static seedRoles()
 * @method static seedPermissions(bool $withSync = false)
 */

class GrantifySeeder extends Facade
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
        return 'grantifySeeder';
    }

}
