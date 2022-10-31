<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Http\Request;
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
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $tcn = 'ahmed';

        return $this->createOtpifyCode($tcn, $otpifiable, null, ['tcn' => $tcn, 'code' => $tcn]);

        // $sendUrl = config('otpify.absher.base_url').'/send';
        // $data = [
        //     'apiKey' => config('otpify.absher.api_key'),
        //     'personId' => $request->validated('notional_id'),
        // ];
        // $response = Http::post($sendUrl, $data)->toPsrResponse();
        // $tcn = $response['tcn'];
        // return $this->createOtpifyCode($tcn, $otpifiable, null, ['tcn' => $tcn, 'code' => $response['code']]);
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
     * @return bool
     */
    public function verify(Request $request, $vid, $code, \Closure $additionalCheckCallback = null): bool
    {
        $checkUrl = config('otpify.absher.base_url').'/check';
        $data = [
            'apiKey' => config('otpify.absher.api_key'),
            'tcn' => $vid,
            'otp' => $code,
        ];
        $response = Http::post($checkUrl, $data)->toPsrResponse();
        if (isset($response['userDetails']) && $userDetails = $response['userDetails']) {
            $otpify = OtpifyCode::where('otp_code', $vid)->orWhere('otp_code', $code)->first();
            $otpify->otpifiable->update(['customer_details' => $userDetails]);

            return true;
        }
    }
}
