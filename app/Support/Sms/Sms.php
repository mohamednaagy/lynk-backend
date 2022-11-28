<?php

namespace App\Support\Sms;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool send(string $message, string $phoneNumber)
 * @method static driver(string $string)
 */
class Sms extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'sms.manager';
    }
}
