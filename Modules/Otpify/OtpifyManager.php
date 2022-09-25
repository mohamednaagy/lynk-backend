<?php

namespace Modules\Otpify;

use Exception;
use Illuminate\Support\Manager;
use Modules\Otpify\Drivers\EmailDriver;
use Modules\Otpify\Traits\CanBeAuthorized;
use Modules\Otpify\Drivers\TwilioSmsDriver;
use Illuminate\Validation\ValidationException;
use Modules\Otpify\Contracts\OtpifyDriverInterface;

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
        try {
            $token = $this->generateRandomToken();
            $data['token'] = hash('sha256', $token);
            $authorizationToken = $this->createAuthorizationToken($data);
            return $authorizationToken->id . '|' . $token;
        } catch (Exception $exception) {
            return $this->generateAuthorizationToken($data);
        }
    }

    /**
     * @param string $token
     * @param string $area
     * @return bool
     */
    public function verifyAuthorizationToken(string $token, string $area): bool
    {
        return $this->verifyToken($token, $area);
    }
}
