<?php

namespace App\Support\Sms\Drivers;

use App\Support\Sms\SmsDriverInterface;

class FakeDriver implements SmsDriverInterface
{
    /**
     * Execute the driver logic.
     *
     * @param  string  $message
     * @param  string  $phoneNumber
     * @return void
     */
    public function send(string $message, string $phoneNumber): void
    {
       //
    }
}
