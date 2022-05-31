<?php

namespace Modules\Permission;

use Illuminate\Support\Manager;
use Modules\Permission\Contracts\GrantifyDriverInterface;
use Modules\Permission\Drivers\SpatieDriver;

class GrantifyManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return config('permission.default', 'spatie');
    }

    /**
     * Access the roles and permissions using spatie.
     *
     * @return GrantifyDriverInterface
     */
    public function createSpatieDriver(): GrantifyDriverInterface
    {
        return new SpatieDriver();
    }

}
