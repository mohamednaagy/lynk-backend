<?php

namespace App\Support\Sms;

use App\Support\Sms\Drivers\FakeDriver;
use App\Support\Sms\Drivers\MsegatDriver;
use Illuminate\Support\Manager;

class SmsManager extends Manager
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
    public function createFakeDriver()
    {
        return new FakeDriver();
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
