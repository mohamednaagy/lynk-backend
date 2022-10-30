<?php

namespace App\Support\MobileVerification\Drivers;

use App\Support\MobileVerification\Contracts\MobileVerifyDriverInterface;

class TestTCCDriver implements MobileVerifyDriverInterface
{
    /**
     * @param  string  $mobileNumber
     * @param  string  $personId
     * @return bool
     */
    public function verify(string $mobileNumber, string $personId): bool
    {
        return true;
    }
}
