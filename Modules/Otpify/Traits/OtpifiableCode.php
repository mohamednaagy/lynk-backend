<?php

namespace Modules\Otpify\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Otpify\Models\OtpifyCode;

trait OtpifiableCode
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

    public function verifyOtpifyCode(OtpifyCode $otpifyCode, $code, \Closure $additionalCheckCallback = null): string
    {
        if ($additionalCheckCallback == false)
            return trans('otpify::verification.additional_check');

        if(!Hash::check($code, $otpifyCode->otp_code))
            return trans('otpify::verification.not_exist');

        if ($otpifyCode->expired_at != null)
            return trans('otpify::verification.used');

        if($this->isCodeExpired($otpifyCode->expiration_date))
            return trans('otpify::verification.expired');

        return 'valid';
    }

    public function getOtpifyCode($vid): OtpifyCode
    {
        return OtpifyCode::where('id', $vid)->first();
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
