<?php

namespace Modules\Otpify\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Models\OtpifyCode;

trait CanOtpifyCode
{
    public function createOtpifyCode($code, array $data = []): OtpifyCode
    {
        return OtpifyCode::create([
            'id' => (string)Str::uuid(),
            'otp_code' => Hash::make($code),
            'expiration_date' => now()->addMinutes(config('otpify.code_expiration_time')),
            'data' => $data
        ]);
    }

    public function verifyOtpifyCode(OtpifyCode $otpifyCode, Request $request, $code, \Closure $additionalCheckCallback = null): void
    {
        if(!Hash::check($code, $otpifyCode->otp_code))
            throw new OtpCodeIncorrectException();

        if ($otpifyCode->expired_at != null)
            throw new OtpCodeAlreadyUsedException();

        if($this->isCodeExpired($otpifyCode->expiration_date))
            throw new OtpCodeExpiredException();

        if ($additionalCheckCallback)
            if (!$additionalCheckCallback($request, $code))
                throw new OtpCodeAdditionalCheckException();
    }

    public function getOtpifyCode($vid): OtpifyCode
    {
        $otpifyCode = OtpifyCode::where('id', $vid)->first();
        if (!$otpifyCode)
            throw new OtpCodeNotFoundException();

        return $otpifyCode;
    }

    public function isCodeExpired($expirationDate): bool
    {
        return $expirationDate->lt(now());
    }

    public function setOtpExpiredAt(OtpifyCode $otpifyCode): void
    {
        $otpifyCode->update(['expired_at' => now()]);
    }
}
