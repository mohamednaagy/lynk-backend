<?php

namespace App\Support\OTP\Core;

interface OtpAdapter
{
    /**
     * @param  string  $nationalId
     * @return mixed
     */
    public function send(string $nationalId): mixed;

    /**
     * @param  string  $tcn
     * @param  string  $otp
     * @return mixed
     */
    public function check(string $tcn, string $otp): mixed;
}
