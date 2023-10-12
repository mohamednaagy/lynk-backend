<?php

namespace App\Support\Sms\Drivers;

use App\Support\Sms\Events\SmsSent;
use App\Support\Sms\SmsDriverInterface;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        try {
            Mail::driver('mailhog')
                ->raw(<<<EOD
                    Mobile: $phoneNumber
                    Message: $message
                    EOD,
                    function (Message $message) {
                        $message->to('sms@lynk.sa')
                            ->subject('SMS Message');
                    }
                );
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage());
        }

        SmsSent::dispatch(
            'fake',
            [],
            [],
            now()
        );
    }
}
