<?php

namespace Modules\Otpify\Contracts;

use Illuminate\Http\Request;
use Modules\Otpify\Models\OtpifyCode;

interface OtpifyDriverInterface
{
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode;

    public function doesRequireVerifyingByOtp(Request $request, Otpifiable $otpifiable): bool;

    public function verify(Request $request, string $vid, string $code, ?\Closure $additionalCheckCallback = null): string|bool;
}
