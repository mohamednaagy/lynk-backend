<?php

namespace Modules\Otpify\Contracts;

use Illuminate\Http\Request;
use Propaganistas\LaravelPhone\PhoneNumber;

interface Otpifiable
{
    /**
     * Check if this user requires verifying by OTP based on role.
     */
    public function doesRequireVerifyingByOtp(Request $request): bool;

    public function getPhoneNumber(): ?PhoneNumber;

    public function getNationalId(): string;

    public function getMorphClass();

    public function getKey();
}
