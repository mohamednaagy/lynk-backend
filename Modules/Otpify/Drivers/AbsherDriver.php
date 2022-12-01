<?php

namespace Modules\Otpify\Drivers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Traits\CanOtpifyCode;

class AbsherDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    protected $baseUrl;

    protected $apiKey;

    public function __construct($baseUrl, $apiKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }

    /**
     * Execute the driver logic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Modules\Otpify\Contracts\Otpifiable  $otpifiable
     * @param  array  $data
     * @return \Modules\Otpify\Models\OtpifyCode
     */
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $sendUrl = $this->url('send');

        $body = [
            'apiKey' => $this->apiKey,
            'personId' => $otpifiable->getNationalId(),
        ];

        $response = Http::post($sendUrl, $body);

        $tcn = $response->json('tcn');

        return $this->createOtpifyCode(null, $otpifiable, $otpifiable, ['tcn' => $tcn]);
    }

    /**
     * Execute the driver logic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Modules\Otpify\Contracts\Otpifiable  $otpifiable
     * @return bool
     */
    public function doesRequireVerifyingByOtp(Request $request, Otpifiable $otpifiable): bool
    {
        return $otpifiable->doesRequireVerifyingByOtp($request);
    }

    /**
     * Execute the driver logic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $vid
     * @param  mixed  $code
     * @param  \Closure|null  $additionalCheckCallback
     * @return string
     */
    public function verify(Request $request, $vid, $code, Closure $additionalCheckCallback = null): string|bool
    {
        $otpifyCode = $this->getOtpifyCode($vid);

        if (! isset($otpifyCode->data['tcn'])) {
            throw new OtpCodeNotFoundException;
        }

        if ($otpifyCode->expired_at != null) {
            throw new OtpCodeAlreadyUsedException();
        }

        if ($this->isCodeExpired($otpifyCode->expiration_date)) {
            throw new OtpCodeExpiredException();
        }

        if ($additionalCheckCallback instanceof Closure && ! $additionalCheckCallback($request, $otpifyCode)) {
            throw new OtpCodeAdditionalCheckException();
        }

        $checkUrl = $this->url('confirm');

        $data = [
            'apiKey' => $this->apiKey,
            'tcn' => $otpifyCode->data['tcn'],
            'otp' => $code,
        ];

        $response = Http::post($checkUrl, $data);

        if (
            $response->json('code') === 600 &&
            $userDetails = $response->json('userDetails')
        ) {
            $otpifyCode->otpifiable->update(['customer_details' => $userDetails]);

            $this->setOtpExpiredAt($otpifyCode);

            return true;
        } else {
            throw new OtpCodeIncorrectException();
        }
    }

    protected function url($path)
    {
        return $this->baseUrl.'/'.ltrim($path, '/');
    }
}
