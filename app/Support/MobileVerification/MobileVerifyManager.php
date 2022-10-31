<?php

namespace App\Support\MobileVerification;

use App\Support\MobileVerification\Contracts\MobileVerifyDriverInterface;
use App\Support\MobileVerification\Drivers\TCCDriver;
use App\Support\MobileVerification\Drivers\TestTCCDriver;
use Illuminate\Support\Manager;

class MobileVerifyManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return config('mobile-verify.default', 'tcc');
    }

    /**
     * Verify mobile number via TCC.
     *
     * @return MobileVerifyDriverInterface
     */
    public function createTccDriver(): MobileVerifyDriverInterface
    {
        return new TCCDriver();
    }

    /**
     * Verify mobile number via Test TCC.
     *
     * @return MobileVerifyDriverInterface
     */
    public function createTestTccDriver(): MobileVerifyDriverInterface
    {
        return new TestTCCDriver();
    }

    /**
     * Get all the drivers.
     *
     * @return array
     */
    public function getMobileVerifyDrivers(): array
    {
        return array_keys(config('mobile-verify.drivers'));
    }
}
