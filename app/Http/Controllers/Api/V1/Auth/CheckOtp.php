<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\CheckOtpRequest;
use Illuminate\Http\Request;

class CheckOtp extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CheckOtpRequest $checkOtpRequest)
    {
        $jsonResponse = app('otp')->check($checkOtpRequest->safeInput('national_id'));
    }
}
