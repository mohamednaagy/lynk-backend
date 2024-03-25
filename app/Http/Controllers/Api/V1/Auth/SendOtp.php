<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Contracts\SendOtp as SendOtpInterface;
use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\SendOtpRequest;
use App\Models\User;
use Modules\Otpify\Models\OtpifyCode;

class SendOtp extends Controller
{
    public function __invoke(SendOtpRequest $request, SendOtpInterface $sendOtp)
    {
        $otpifyCode = OtpifyCode::where('id', $request->validated('vid'))->first();
        if (
            ! $otpifyCode // not exists
            || $otpifyCode->expired_at // already used
            || ! $otpifyCode->expiration_date->lt(now()) // not expired
            || ! $otpifyCode->otpifiable instanceof User // is not a user
            || ! ($newOtpifyCode = $sendOtp->handle($otpifyCode->otpifiable, $request)) // otp not sent
        ) {
            return $this->errorResponse(
                message: trans('otpify::response.something_went_wrong'),
                code: ErrorCode::OTPIFY_DRIVERS_CONFIGURATION
            );
        }

        return $this->successResponse([
            'vid' => $newOtpifyCode->id,
        ]);
    }
}
