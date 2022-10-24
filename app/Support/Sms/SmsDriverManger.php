<?php

namespace App\Services;

use Illuminate\Support\Manager;

class SmsDriverManger extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('sms.default');
    }
}
