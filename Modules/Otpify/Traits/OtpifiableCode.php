<?php

namespace Modules\Otpify\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Otpify\Models\OtpifyCode;

trait OtpifiableCode
{
    public function createOtpifyCode(array $data, $code): OtpifyCode
    {
        return OtpifyCode::create([
            'id' => (string)Str::uuid(),
            'otp_code' => Hash::make($code),
            'expiration_date' => now()->addMinutes(config('otpify.code_expiration_time')),
            'data' => $data
        ]);
    }

    public function getOtpifyCode($vid, $code): OtpifyCode
    {
        return OtpifyCode::where('id', $vid)->first();
    }

    public function isCodeExpired($expiredAt): bool
    {
        return $expiredAt->lt(now());
    }
}
