<?php

namespace App\Support\Sms\Drivers;

use App\Exceptions\BalanceIsNotEnoughException;
use App\Exceptions\InvalidLoginInfoException;
use App\Exceptions\MobileNumbersIsNotCorrectException;
use App\Exceptions\MSGDuplicatedException;
use App\Exceptions\MSGServiceStoppedException;
use App\Support\Sms\Events\SmsSent;
use App\Support\Sms\SmsDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MsegatDriver implements SmsDriverInterface
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
            'msg' => $message."\n"."\n".Str::random(6),
        ];

        $response = Http::post($this->baseUrl, $body);

        if (is_null($response->json())) {
            throw new MSGServiceStoppedException('Msegat Driver return NULL response', [
                'body' => json_encode($body),
                'response' => json_encode([
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]),
            ]);
        }

        SmsSent::dispatch(
            'msegat',
            [
                'url' => $this->baseUrl,
                'body' => $body,
            ],
            $response->json(),
            now()
        );

        $code = $response->object()->code;

        $match = match ($code) {
            '1020' => new InvalidLoginInfoException(),
            '1060' => new BalanceIsNotEnoughException(),
            '1061' => new MSGDuplicatedException(),
            '1120' => new MobileNumbersIsNotCorrectException(),
            default => null,
        };

        if ($match === null) {
            return;
        }

        throw $match;
    }

    protected function url($path)
    {
        return $this->baseUrl.'/'.ltrim($path, '/');
    }
}
