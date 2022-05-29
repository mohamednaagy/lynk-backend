<?php

namespace Modules\Otpify\Facades;

use Illuminate\Support\Facades\Facade;

class Otpify extends Facade
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
        return 'otpify';
    }

}
