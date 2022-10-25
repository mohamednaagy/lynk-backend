<?php

namespace App\Support\Sms;

class Sms
{
    /**
     * @param  string  $message
     * @param  string  $phoneNumber
     * @return void
     */
    public static function send($message, $phoneNumber): void
    {
        // $this->send();
        $manager = app('Sms');
        $manager->driver('msegat')->send($message, $phoneNumber);
    }
}
