<?php

namespace Modules\Otpify;

use Illuminate\Support\Manager;
use Modules\Otpify\Drivers\EmailDriver;
use Modules\Otpify\Traits\CanBeAuthorized;
use Modules\Otpify\Drivers\TwilioSmsDriver;
use Illuminate\Validation\ValidationException;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\AuthorizedTokenNotFoundException;

class OtpifyManager extends Manager
{
    use CanBeAuthorized;

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
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

    /**
     * @param array $data
     * @return string
     * @throws ValidationException
     */
    public function generateAuthorizationToken(array $data): string
    {
        $token = $this->generateRandomToken();
        $data['token'] = $token;
        $this->createAuthorizationToken($data);
        return $token;
    }

    /**
     * @throws AuthorizedTokenNotFoundException
     */
    public function verifyAuthorizationToken(string $token): bool
    {
        return $this->verifyToken($token) ?? throw new AuthorizedTokenNotFoundException();
    }
}
