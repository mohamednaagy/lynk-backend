<?php

namespace Modules\Otpify\Drivers;

use Closure;
use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Traits\CanOtpifyCode;

class FakeAbsherDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    /**
     * Execute the driver logic.
     */
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        return $this->createOtpifyCode(null, $otpifiable, $otpifiable);
    }

    /**
     * Execute the driver logic.
     */
    public function doesRequireVerifyingByOtp(Request $request, Otpifiable $otpifiable): bool
    {
        return $otpifiable->doesRequireVerifyingByOtp($request);
    }

    /**
     * Execute the driver logic.
     *
     * @param  mixed  $vid
     * @param  mixed  $code
     *
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpCodeNotFoundException
     */
    public function verify(Request $request, $vid, $code, ?Closure $additionalCheckCallback = null): string|bool
    {
        $otpifyCode = $this->getOtpifyCode($vid);
        if ($otpifyCode->expired_at != null) {
            throw new OtpCodeAlreadyUsedException;
        }

        if ($this->isCodeExpired($otpifyCode->expiration_date)) {
            throw new OtpCodeExpiredException;
        }

        if ($additionalCheckCallback instanceof Closure && ! $additionalCheckCallback($request, $otpifyCode)) {
            throw new OtpCodeAdditionalCheckException;
        }

        if ($code === '2023') {

            return true;
            // return $this->createAuthorizationToken($request->all());
        } else {
            throw new OtpCodeIncorrectException;
        }
    }
}
