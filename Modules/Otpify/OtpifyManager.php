<?php

namespace Modules\Otpify;

use Illuminate\Support\Manager;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Drivers\EmailDriver;
use Modules\Otpify\Drivers\TwilioSmsDriver;

class OtpifyManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return config('otpify.default', 'email');
    }

    /**
     * Send OTP via email.
     *
     * @return OtpifyDriverInterface
     */
    public function createEmailDriver(): OtpifyDriverInterface
    {
        return new EmailDriver();
    }

    /**
     * Send OTP via SMS.
     *
     * @return OtpifyDriverInterface
     */
    public function createTwilioDriver(): OtpifyDriverInterface
    {
        return new TwilioSmsDriver();
    }

    /**
     * Get all the drivers.
     *
     * @return array
     */
    public function getOtpifyDrivers(): array
    {
        return array_keys(config('otpify.drivers'));
    }

}
