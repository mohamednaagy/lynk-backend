<?php

namespace Modules\Otpify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static verifyAuthorizationToken(mixed $input)
 * @method static generateAuthorizationToken(array $data)
 * @method static driver($otp_driver)
 * @method static getOtpifyDrivers()
 */
class Otpify extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'otpify';
    }
}
