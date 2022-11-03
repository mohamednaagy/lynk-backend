<?php

namespace App\Support\SMS;

use App\Support\SMS\Drivers\FakeSMSDriver;
use App\Support\SMS\Drivers\MsegatDriver;
use Illuminate\Support\Manager;

class SMSManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return config('sms.provider', 'msegat');
    }

    /**
     * Send SMS via MSEGAT.
     */
    public function createMsegatDriver()
    {
        return new MsegatDriver(
            baseUrl: config('sms.msegat.url'),
            apiKey: config('sms.msegat.api_key')
        );
    }

    /**
     * Send SMS via Fake.
     */
    public function createFakeSMSDriver()
    {
        return new FakeSMSDriver();
    }

    /**
     * Get a driver instance.
     *
     * @param  string|null  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        // If the given driver has not been created before, we will create the instances
        // here and cache it so we can return it next time very quickly. If there is
        // already a driver created by this name, we'll just return that instance.
        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }
}
