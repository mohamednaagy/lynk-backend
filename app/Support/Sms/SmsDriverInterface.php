<?php

namespace App\Support\Sms;

interface SmsDriverInterface
{
    /**
     * Execute the driver logic.
     */
    public function send(string $message, string $phoneNumber): void;
}
