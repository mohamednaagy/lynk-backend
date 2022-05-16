<?php

namespace Modules\TwoFactorAuth;

use Illuminate\Support\Manager;
use Modules\TwoFactorAuth\Contracts\TwoFactorAuthInterface;
use Modules\TwoFactorAuth\Drivers\EmailTwoFactorAuthDriver;
use Modules\TwoFactorAuth\Drivers\TwilioTwoFactorAuthDriver;

class TwoFactorAuthManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return config('twofactorauth.default') ?: 'email';
    }

    /**
     * Send OTP via email.
     *
     * @return TwoFactorAuthInterface
     */
    public function createEmailDriver(): TwoFactorAuthInterface
    {
        return new EmailTwoFactorAuthDriver();
    }

    /**
     * Send OTP via SMS.
     *
     * @return TwoFactorAuthInterface
     */
    public function createTwilioDriver(): TwoFactorAuthInterface
    {
        return new TwilioTwoFactorAuthDriver();
    }

}
