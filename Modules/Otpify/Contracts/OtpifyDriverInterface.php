<?php

namespace Modules\Otpify\Contracts;

use Illuminate\Http\Request;
use Modules\Otpify\Models\OtpifyCode;

interface OtpifyDriverInterface
{
    /**
     * Execute the driver logic.
     *
     * @param  Request  $request
     * @param  Otpifiable  $otpifiable
     * @param  array  $data
     * @return OtpifyCode
     */
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode;

    /**
     * Execute the driver logic.
     *
     * @param  Request  $request
     * @param  Otpifiable  $otpifiable
     * @return bool
     */
    public function doesRequireVerifyingByOtp(Request $request, Otpifiable $otpifiable): bool;

    /**
     * Execute the driver logic.
     *
     * @param  Request  $request
     * @param $vid
     * @param $code
     * @param  \Closure|null  $additionalCheckCallback
     * @return string
     */
    public function verify(Request $request, $vid, $code, \Closure $additionalCheckCallback = null): string;
}
