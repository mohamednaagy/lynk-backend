<?php

namespace App\Support\Sms;

use App\Exceptions\BalanceIsNotEnoughException;
use App\Exceptions\InvalidLoginInfoException;
use App\Exceptions\MobileNumbersIsNotCorrectException;
use App\Exceptions\MSGDuplicatedException;
use Illuminate\Support\Facades\Http;

class Sms
{
    public static function sendSMS($message, $phoneNumber)
    {
        if (config('app.sms_providers.name') == 'MSEGAT') {
            // call api to send message
            $response = Http::post('https://www.msegat.com/gw/sendsms.php', [
                'userName' => config('app.sms_providers.username'),
                'numbers' => $phoneNumber,
                'userSender' => env('APP_NAME'),
                'apiKey' => config('app.sms_providers.api_key'),
                'msg' => $message,
            ]);
            $code = $response->object()->code;
            $e = '';
            if ($code == 1) {
                return response()->json(['message' => $message]);
            } elseif ($code == 1020) {
                $e = throw new InvalidLoginInfoException();
            } elseif ($code == 1060) {
                $e = throw new BalanceIsNotEnoughException();
            } elseif ($code == 1061) {
                $e = throw new MSGDuplicatedException();
            } elseif ($code == 1120) {
                $e = throw new MobileNumbersIsNotCorrectException();
            }

            return  $e;
        }
        // return $response;
    }
}
