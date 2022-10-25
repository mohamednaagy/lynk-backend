<?php

namespace App\Support\Sms;

use Illuminate\Support\Manager;

class SmsManger extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return config('sms_providers.msegat');
    }

    /**
     * Send SMS via MSEGAT.
     */
    public function createMsegatDriver()
    {
        return new MsegatDriver();
    }
}
