<?php


namespace Modules\Otpify\Traits;


use Illuminate\Support\Str;
use Modules\Otpify\Entities\OtpifyCode;

trait GenerateOtpifyCode
{
    public function createOtpifyCode(array $data): OtpifyCode
    {
        $code = generateRandomCode(4);

        $otpCode                = new OtpifyCode();
        $otpCode->vid           = (string)Str::uuid();
        $otpCode->otp_code      = $code;
        $otpCode->expired_at    = now()->addMinutes(10);
        $otpCode->data          = $data;
        $otpCode->save();

        return $otpCode;
    }
}
