<?php

namespace App\Support\SmsFacade;

use Illuminate\Support\Facades\Facade;

class SmsFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'sms';
    }
}
