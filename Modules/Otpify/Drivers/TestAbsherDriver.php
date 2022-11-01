<?php

namespace Modules\Otpify\Drivers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Traits\CanOtpifyCode;

class TestAbsherDriver implements OtpifyDriverInterface
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
        $code = '2023';

        return $this->createOtpifyCode($code, $otpifiable, $otpifiable);
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
    public function verify(Request $request, $vid, $code, Closure $additionalCheckCallback = null): string
    {
        $otpifyCode = OtpifyCode::where('otp_code', $vid)
            ->orWhere(
                'otp_code',
                $code
            )
            ->latest(
            )
            ->firstOrFail(
            );

        if ($otpifyCode->expired_at != null) {
            throw new OtpCodeAlreadyUsedException();
        }

        $checkUrl = $this->url('check');

        $data = [
            'apiKey' => $this->apiKey,
            'tcn' => $vid,
            'otp' => $code,
            'code' => 600,
            'message' => 'success',
            'userDetails' => [
                'name' => 'user name',
            ],
        ];

        Http::fake(
            [
                $checkUrl => Http::response($data),
            ]
        );
        $response = Http::post($checkUrl, $data);

        if (
            Arr::get($response, 'code') === 600 &&
            isset($response['userDetails']) &&
            $userDetails = $response['userDetails']
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
