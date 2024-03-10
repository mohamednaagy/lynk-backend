<?php

namespace App\Actions;

use App\Actions\Contracts\SendOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Otpify\Facades\Otpify;
use Modules\Otpify\Models\OtpifyCode;

class SendOtpAction implements SendOtp
{
    public function handle(User $user, ?Request $request = null): ?OtpifyCode
    {
        $otpify = Otpify::driver(config('otpify.default_auth_driver'));

        if (! $otpify->doesRequireVerifyingByOtp($request, $user)) {
            return null;
        }

        return $otpify->send($request, $user);
    }
}
