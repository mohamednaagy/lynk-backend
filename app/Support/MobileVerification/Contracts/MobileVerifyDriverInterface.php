<?php

namespace App\Support\MobileVerification\Contracts;

interface MobileVerifyDriverInterface
{
    /**
     * Execute the driver logic.
     *
     * @param  string  $mobileNumber
     * @param  string  $personId
     * @return bool
     */
    public function verify(string $mobileNumber, string $personId): bool;
}
