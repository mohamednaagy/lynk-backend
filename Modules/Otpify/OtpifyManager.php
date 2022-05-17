<?php

namespace Modules\Otpify;

use Illuminate\Support\Manager;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Drivers\EmailOtpifyDriver;
use Modules\Otpify\Drivers\TwilioOtpifyDriver;

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
        return new EmailOtpifyDriver();
    }

    /**
     * Send OTP via SMS.
     *
     * @return OtpifyDriverInterface
     */
    public function createTwilioDriver(): OtpifyDriverInterface
    {
        return new TwilioOtpifyDriver();
    }

}
