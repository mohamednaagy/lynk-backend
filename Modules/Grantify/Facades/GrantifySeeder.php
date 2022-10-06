<?php

namespace Modules\Grantify\Facades;

use Illuminate\Support\Facades\Facade;
use RuntimeException;

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
