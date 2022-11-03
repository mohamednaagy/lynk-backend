<?php

namespace App\Support\SMS;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool send(string $message, string $phoneNumber)
 */
class SMS extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'sms.manager';
    }
}
