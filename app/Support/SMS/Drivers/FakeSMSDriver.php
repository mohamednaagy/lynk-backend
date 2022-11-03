<?php

namespace App\Support\SMS\Drivers;

use App\Support\SMS\SMSDriverInterface;

class FakeSMSDriver implements SMSDriverInterface
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
