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
            if ($code == 1) {
                return response()->json(['message' => $message]);
            } elseif ($code == 1020) {
                //1020 - Invalid login info
                throw new InvalidLoginInfoException();
            } elseif ($code == 1060) {
                //1060 - Balance is not enough
                throw new BalanceIsNotEnoughException();
            } elseif ($code == 1061) {
                //1061 - MSG duplicated
                throw new MSGDuplicatedException();
            } elseif ($code == 1120) {
                //1120 - Mobile numbers is not correct
                throw new MobileNumbersIsNotCorrectException();
            }

            return $code;
        }
        // return $response;
    }
}
