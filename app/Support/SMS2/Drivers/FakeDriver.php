<?php

namespace App\Support\SMS2\Drivers;

use App\Support\SMS2\SmsDriverInterface;

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
