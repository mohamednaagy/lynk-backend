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
        $sendUrl = config('otpify.absher.base_url').'/send';
        $data = [
            'apiKey' => config('otpify.absher.api_key'),
            'personId' => $request->validated('notional_id'),
        ];
        $response = Http::post($sendUrl, $data)->toPsrResponse();

        return $this->createOtpifyCode(null, $otpifiable->id, ['tcn' => $response['tcn'], 'code' => $response['code']]);
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
    public function verify(\Illuminate\Http\Request $request, $vid, $code, \Closure $additionalCheckCallback = null): bool
    {
        $checkUrl = config('otpify.absher.base_url').'/check';
        $data = [
            'apiKey' => config('otpify.absher.api_key'),
            'tcn' => $vid,
            'otp' => $code,
        ];
        $response = Http::post($checkUrl, $data)->toPsrResponse();

        return isset($response['userDetails']);
    }
}
