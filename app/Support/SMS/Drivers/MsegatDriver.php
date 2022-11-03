<?php

namespace App\Support\Sms\Drivers;

use App\Exceptions\BalanceIsNotEnoughException;
use App\Exceptions\InvalidLoginInfoException;
use App\Exceptions\MobileNumbersIsNotCorrectException;
use App\Exceptions\MSGDuplicatedException;
use App\Support\SMS\SMSDriverInterface;
use Illuminate\Support\Facades\Http;

class MsegatDriver implements SMSDriverInterface
{
    protected string $baseUrl;

    protected string $apiKey;

    public function __construct($baseUrl, $apiKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }

    /**
     * Execute the driver logic.
     *
     * @param  string  $message
     * @param  string  $phoneNumber
     * @return void
     *
     * @throws BalanceIsNotEnoughException
     * @throws InvalidLoginInfoException
     * @throws MSGDuplicatedException
     * @throws MobileNumbersIsNotCorrectException
     * @throws \ErrorException
     */
    public function send(string $message, string $phoneNumber): void
    {
        $body = [
            'userName' => config('sms.msegat.user_name'),
            'numbers' => $phoneNumber,
            'userSender' => config('sms.msegat.sender_name'),
            'apiKey' => $this->apiKey,
            'msg' => $message,
        ];

        $response = Http::post($this->baseUrl, $body);

        // store the response data of the sms for tracking
        activity()
            ->event('verified')
            ->log($response);

        $code = $response->object()->code;
        throw match ($code) {
            '1020' => new InvalidLoginInfoException(),
            '1060' => new BalanceIsNotEnoughException(),
            '1061' => new MSGDuplicatedException(),
            '1120' => new MobileNumbersIsNotCorrectException(),
            default => new \ErrorException('Error found'),
        };
    }

    protected function url($path)
    {
        return $this->baseUrl.'/'.ltrim($path, '/');
    }
}
