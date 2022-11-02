<?php

namespace Modules\Otpify;

use Illuminate\Support\Manager;
use Illuminate\Validation\ValidationException;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Drivers\AbsherDriver;
use Modules\Otpify\Drivers\EmailDriver;
use Modules\Otpify\Drivers\FakeAbsherDriver;
use Modules\Otpify\Drivers\TwilioSmsDriver;
use Modules\Otpify\Traits\CanBeAuthorized;

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
     * Send OTP via absher.
     *
     * @return OtpifyDriverInterface
     */
    public function createAbsherDriver(): OtpifyDriverInterface
    {
        return new AbsherDriver(
            baseUrl: config('otpify.drivers.absher.base_url'),
            apiKey: config('otpify.drivers.absher.api_key')
        );
    }

    /**
     * Send OTP via absher.
     *
     * @return OtpifyDriverInterface
     */
    public function createTestAbsherDriver(): OtpifyDriverInterface
    {
        return new FakeAbsherDriver();
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
     * @param  array  $data
     * @return string
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function generateAuthorizationToken(array $data): string
    {
        try {
            $plainTextToken = $this->generateRandomToken();

            $data['token'] = hash('sha256', $plainTextToken);

            $authorizationToken = $this->createAuthorizationToken($data);

            return $authorizationToken->id.'|'.$plainTextToken;
        } catch (ValidationException $exception) {
            return $this->generateAuthorizationToken($data);
        }
    }

    /**
     * @param  string  $token
     * @param  string  $area
     * @return bool
     */
    public function verifyAuthorizationToken(string $token, string $area): bool
    {
        return $this->verifyToken($token, $area);
    }
}
