<?php

namespace App\Support\Sms\Drivers;

use App\Exceptions\BalanceIsNotEnoughException;
use App\Exceptions\InvalidLoginInfoException;
use App\Exceptions\MobileNumbersIsNotCorrectException;
use App\Exceptions\MSGDuplicatedException;
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
            config('sms.msegat.url'),
            [
                'user_name' => config('sms.msegat.user_name'),
                'numbers' => $phoneNumber,
                'user_sender' => config('sms.msegat.sender_name'),
                'api_key' => config('sms.msegat.api_key'),
                'msg' => $message,
            ]
        );
        // store the response data of the sms for tracking
        activity()
            ->event('verified')
            ->log($response);

        $code = $response->object()->code;
        switch ($code) {
            case '1':
                // message sent successfuly
                break;
            case '1020':
                throw new InvalidLoginInfoException();
                break;
            case '1060':
                throw new BalanceIsNotEnoughException();
                break;
            case '1061':
                throw new MSGDuplicatedException();
                break;
            case '1120':
                throw new MobileNumbersIsNotCorrectException();
            default:
                // throw error
                throw new \ErrorException('Error found');
                break;
        }
    }
}
