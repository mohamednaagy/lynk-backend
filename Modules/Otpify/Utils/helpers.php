<?php

use Modules\Otpify\Contracts\Otpifiable;

if (! function_exists('generateRandomCode')) {
    function generateRandomCode($digits)
    {
        return rand(pow(10, $digits - 1), pow(10, $digits) - 1);
    }
}

if (! function_exists('getOtpifiablePhoneNumber')) {
    function getOtpifiablePhoneNumber(Otpifiable $otpifiable)
    {
        $phoneNumber = $otpifiable->phone_number;

        if (method_exists($otpifiable, 'routeOtpForPhoneNumber')) {
            $phoneNumber = $otpifiable->routeOtpForPhoneNumber();
        }

        return $phoneNumber;
    }
}
