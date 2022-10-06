<?php

namespace Modules\Otpify\Contracts;

use Illuminate\Http\Request;

interface Otpifiable
{
    /**
     * Check if this user requires verifying by OTP based on role.
     *
     * @param  Request  $request
     * @return bool
     */
    public function doesRequireVerifyingByOtp(Request $request): bool;
}
