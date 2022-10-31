<?php

namespace App\Support\Sms\Drivers;

interface SmsDriverInterface
{
    /**
     * Execute the driver logic.
     *
     * @param  string  $message
     * @param  string  $phoneNumber
     * @return void
     */
    public function send($message, $phoneNumber): void;
}
