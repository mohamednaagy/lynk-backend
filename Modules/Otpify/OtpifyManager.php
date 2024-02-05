<?php

namespace Modules\Otpify;

use Illuminate\Support\Manager;
use Illuminate\Validation\ValidationException;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Drivers\AbsherDriver;
use Modules\Otpify\Drivers\AuthDriver;
use Modules\Otpify\Drivers\EmailDriver;
use Modules\Otpify\Drivers\FakeAbsherDriver;
use Modules\Otpify\Drivers\TwilioSmsDriver;
use Modules\Otpify\Traits\CanBeAuthorized;

class OtpifyManager extends Manager
{
    use CanBeAuthorized;

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return config('otpify.default', 'email');
    }

    /**
     * Send OTP via email.
     */
    public function createAuthDriver(): OtpifyDriverInterface
    {
        return new AuthDriver();
    }

    /**
     * Send OTP via email.
     */
    public function createEmailDriver(): OtpifyDriverInterface
    {
        return new EmailDriver();
    }

    /**
     * Send OTP via SMS.
     */
    public function createTwilioDriver(): OtpifyDriverInterface
    {
        return new TwilioSmsDriver();
    }

    /**
     * Send OTP via absher.
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
     */
    public function createFakeAbsherDriver(): OtpifyDriverInterface
    {
        return new FakeAbsherDriver();
    }

    /**
     * Get all the drivers.
     */
    public function getOtpifyDrivers(): array
    {
        return array_keys(config('otpify.drivers'));
    }

    /**
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

    public function verifyAuthorizationToken(string $token, string $area): bool
    {
        return $this->verifyToken($token, $area);
    }
}
