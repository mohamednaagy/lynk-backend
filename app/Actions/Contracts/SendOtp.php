<?php

namespace App\Actions\Contracts;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Otpify\Models\OtpifyCode;

interface SendOtp
{
    public function handle(User $user, ?Request $request): ?OtpifyCode;
}
