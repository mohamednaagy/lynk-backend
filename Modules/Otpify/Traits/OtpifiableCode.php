<?php

namespace Modules\Otpify\Traits;

use Illuminate\Support\Str;
use Modules\Otpify\Models\OtpifyCode;

trait OtpifiableCode
{
    public function createOtpifyCode(array $data): OtpifyCode
    {
        $code = generateRandomCode(config("otpify.code_length"));

        return OtpifyCode::create([
            'id' => (string)Str::uuid(),
            'otp_code' => $code,
            'expired_at' => now()->addMinutes(10),
            'data' => $data
        ]);
    }

    public function getOtpifyCode($vid, $code): OtpifyCode
    {
        return OtpifyCode::where(['id' => $vid, 'otp_code' => $code])->first();
    }

    public function codeIsValid($expiredAt): bool
    {
        return $expiredAt->gt(now());
    }
}
