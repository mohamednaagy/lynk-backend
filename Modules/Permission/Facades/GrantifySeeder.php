<?php

namespace Modules\Permission\Facades;

use Illuminate\Support\Facades\Facade;

class GrantifySeeder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'grantifySeeder';
    }

}
