<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\CheckOtpRequest;
use Illuminate\Http\Request;

class CheckOtp extends Controller
{
    /**
     *  Handle the incoming request.
     *
     * @param  CheckOtpRequest  $checkOtpRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(CheckOtpRequest $checkOtpRequest)
    {
        try {
            $verify = Otpify::driver('absher')->verify($checkOtpRequest, $checkOtpRequest->safeInput('tcn'), $checkOtpRequest->safeInput('otp'));
            $response = ($verify == true) ?
                $this->successResponse()
                : $this->errorResponse();
        } catch (\Throwable $th) {
            //throw $th;
            $response = $this->errorResponse($th->getMessage());
        }

        return $response;
    }
}
