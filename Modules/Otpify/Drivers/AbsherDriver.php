<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Support\Facades\Http;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Traits\CanOtpifyCode;

class AbsherDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    /**
     * Execute the driver logic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Modules\Otpify\Contracts\Otpifiable  $otpifiable
     * @param  array  $data
     * @return \Modules\Otpify\Models\OtpifyCode
     */
    public function send(\Illuminate\Http\Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $sendUrl = config('otpify.absher.send_url');
        $data = [
            'apiKey' => config('otpify.absher.api_key'),
            'personId' => $request->safeInput('notional_id'),
        ];
        $response = Http::post($sendUrl, $data)->toPsrResponse();
        $code = $response['code'];

        return $this->createOtpifyCode($code, 1, []);
    }

    /**
     * Execute the driver logic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Modules\Otpify\Contracts\Otpifiable  $otpifiable
     * @return bool
     */
    public function doesRequireVerifyingByOtp(\Illuminate\Http\Request $request, Otpifiable $otpifiable): bool
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
     * @return bool
     */
    public function verify(\Illuminate\Http\Request $request, $tcn, $otp, \Closure $additionalCheckCallback = null): bool
    {
        $checkUrl = config('otpify.absher.check_url');
        $data = [
            'apiKey' => config('otpify.absher.api_key'),
            'tcn' => $tcn,
            'otp' => $otp,
        ];
        $response = Http::post($checkUrl, $data)->toPsrResponse();

        return isset($response['userDetails']);
    }
}
