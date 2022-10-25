<?php

namespace App\Support\Sms;

use App\Exceptions\BalanceIsNotEnoughException;
use App\Exceptions\InvalidLoginInfoException;
use App\Exceptions\MobileNumbersIsNotCorrectException;
use App\Exceptions\MSGDuplicatedException;
use App\Models\SmsReport;
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

        $code = $response->object()->code;

        // store the response data of the sms for tracking
        SmsReport::create([
            'response_data' => $response,
        ]);

        // switch ($code) {
        //     case '1':
        //         // message sent successfuly
        //         break;
        //     case '1020':
        //         throw new InvalidLoginInfoException();
        //         break;
        //     case '1060':
        //         throw new BalanceIsNotEnoughException();
        //         break;
        //     case '1061':
        //         throw new MSGDuplicatedException();
        //         break;
        //     case '1120':
        //         throw new MobileNumbersIsNotCorrectException();
        //         break;
        //     case '1110':
        //         throw new MobileNumbersIsNotCorrectException();
        //         break;
        //     default:
        //         // throw error
        //         throw new \ErrorException('Error found');
        //         break;
        // }
    }
}
