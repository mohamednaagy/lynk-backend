<?php

namespace App\Support\Sms;

use Illuminate\Support\Facades\Http;

class MsegatDriver implements SmsDriverInterface
{
    /**
     * Execute the driver logic.
     *
     * @param  string  $message
     * @param  string  $phoneNumber
     * @return void
     */
    public function send($message, $phoneNumber): void
    {
        $response = Http::post(
            config('app.sms_providers.msegat.url'),
            [
                'userName' => config('app.sms_providers.msegat.username'),
                'numbers' => $phoneNumber,
                'userSender' => env('APP_NAME'),
                'apiKey' => config('app.sms_providers.msegat.api_key'),
                'msg' => $message,
            ]
        );
        activity()
            ->event('verified')
            ->log($response);
    }
}
