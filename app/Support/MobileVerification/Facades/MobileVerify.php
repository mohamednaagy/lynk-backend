<?php

namespace App\Support\MobileVerification\Facades;

use Illuminate\Support\Facades\Facade;

class MobileVerify extends Facade
{
    /**
     * Get the registered name of the component.
     *
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mobile-verify';
    }
}
