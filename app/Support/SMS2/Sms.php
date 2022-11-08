<?php

namespace App\Support\SMS2;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool send(string $message, string $phoneNumber)
 */
class Sms extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'sms.manager';
    }
}
