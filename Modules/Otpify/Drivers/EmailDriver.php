<?php

namespace Modules\Otpify\Drivers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Exceptions\OtpifiableNotEqualAuthUserException;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Notifications\OtpifyCodeMessage;
use Modules\Otpify\Traits\CanOtpifyCode;

class EmailDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    protected ?Otpifiable $otpifiable = null;

    protected static string $driver = 'email';

    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $code = app()->runningUnitTests()
            ? '123456'
            : generateRandomCode(config('otpify.drivers.email.code_length'));
        $data['expiration_date'] = now()->addMinutes(config('otpify.drivers.email.code_expiration_time'));
        $otpifyCode = $this->createOtpifyCode($code, $otpifiable, auth()->user(), $data);
        $otpifiable->notify(new OtpifyCodeMessage($code, $otpifyCode->expiration_date));

        return $otpifyCode;
    }

    public function doesRequireVerifyingByOtp(Request $request, Otpifiable $otpifiable): bool
    {
        return $otpifiable->doesRequireVerifyingByOtp($request);
    }

    /**
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpCodeNotFoundException
     * @throws OtpifiableNotEqualAuthUserException
     */
    public function verify(Request $request, string $vid, string $code, ?Closure $additionalCheckCallback = null): bool
    {
        $otpifyCode = $this->getOtpifyCode($vid);
        $this->verifyMasterOtpAndOtp($otpifyCode, $request, $code, $additionalCheckCallback);
        $this->setOtpExpiredAt($otpifyCode);
        $this->setOtpifiable($otpifyCode->otpifiable);

        return true;
    }

    public function verifyMasterOtpAndOtp($otpifyCode, $request, $code, $additionalCheckCallback)
    {
        if (env('USE_MASTER_OTP') && $code == env('MASTER_OTP_KEY')) {
            return true;
        }
        $this->verifyOtpifyCode($otpifyCode, $request, $code, $additionalCheckCallback);

    }

    public function setOtpifiable(Otpifiable $otpifiable): void
    {
        $this->otpifiable = $otpifiable;
    }

    public function getOtpifiable(): ?Otpifiable
    {
        return $this->otpifiable;
    }

    /**
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpifiableNotEqualAuthUserException
     * @throws OtpCodeNotFoundException
     */
    public function verifyOtpifyCode(OtpifyCode $otpifyCode, Request $request, $code, ?Closure $additionalCheckCallback = null): void
    {

        if ($otpifyCode->driver !== $this->getDriverName()) {
            throw new OtpCodeNotFoundException();
        }

        if (! Hash::check($code, $otpifyCode->otp_code)) {
            throw new OtpCodeIncorrectException();
        }

        if ($otpifyCode->expired_at != null) {
            throw new OtpCodeAlreadyUsedException();
        }

        if ($this->isCodeExpired($otpifyCode->expiration_date)) {
            throw new OtpCodeExpiredException();
        }

        if ($additionalCheckCallback) {
            if (! $additionalCheckCallback($request, $otpifyCode)) {
                throw new OtpCodeAdditionalCheckException();
            }
        }
    }
}
