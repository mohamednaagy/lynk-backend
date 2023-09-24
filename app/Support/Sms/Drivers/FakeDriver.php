<?php

namespace App\Support\Sms\Drivers;

use App\Support\Sms\Events\SmsSent;
use App\Support\Sms\SmsDriverInterface;
use Illuminate\Support\Facades\Storage;

class FakeDriver implements SmsDriverInterface
{
    /**
     * Execute the driver logic.
     */
    public function send(string $message, string $phoneNumber): void
    {
        if (config('sms.logging')) {
            Storage::disk('public')
                ->prepend('logs/sms.log', $phoneNumber."\n".$message."\n");
        }

        SmsSent::dispatch(
            'fake',
            [],
            [],
            now()
        );
    }
}
