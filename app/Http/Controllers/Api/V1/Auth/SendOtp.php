<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\SendOtpRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Otpify\Facades\Otpify;

class SendOtp extends Controller
{
    /**
     *  Handle the incoming request.
     *
     * @param  SendOtpRequest  $sendOtpRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(SendOtpRequest $sendOtpRequest)
    {
        try {
            Otpify::driver('absher')->send($sendOtpRequest, User::first());
            $response = $this->successResponse();
        } catch (\Throwable $th) {
            $response = $this->errorResponse($th->getMessage());
        }

        return $response;
    }
}
