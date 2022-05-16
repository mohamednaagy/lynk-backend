<?php

namespace Modules\TwoFactorAuth\Facades;

use Illuminate\Support\Facades\Facade;

class TwoFactorAuth extends Facade
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
        return 'twoFactorAuth';
    }

}
