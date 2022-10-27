<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\SendOtpRequest;

class SendOtp extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(SendOtpRequest $sendOtpRequest)
    {
        app('otp')->send($sendOtpRequest->safeInput('tcn'), $sendOtpRequest->safeInput('otp'));
    }
}
