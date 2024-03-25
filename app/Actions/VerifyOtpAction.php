<?php

namespace App\Actions;

use App\Actions\Contracts\VerifyOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Otpify\Facades\Otpify;
use Modules\Otpify\Models\OtpifyCode;

class VerifyOtpAction implements VerifyOtp
{
    public function handle(string $vid, string $code, ?Request $request = null): ?User
    {
        $otpify = Otpify::driver(config('otpify.default_auth_driver'));

        $otpify->verify($request, $vid, $code, function (Request $request, OtpifyCode $otpCode) {
            if (! $otpCode->otpifiable instanceof User) {
                return false;
            }

            $user = $otpCode->otpifiable;

            if ($user->company_id) {
                tenancy()->initialize($user->company_id);
            }

            Cache::put(
                'has_verified_otp_'.$otpCode->otpifiable->id,
                true,
                now()->addMinutes(5)
            );

            return true;
        });

        return $otpify->getOtpifiable();
    }
}
