<?php

namespace App\Support\MobileVerification\Contracts;

use Propaganistas\LaravelPhone\PhoneNumber;

interface MobileVerifyDriverInterface
{
    /**
     * Execute the driver logic.
     *
     * @param  string  $mobileNumber
     */
    public function verify(PhoneNumber $mobileNumber, string $personId): bool;
}
