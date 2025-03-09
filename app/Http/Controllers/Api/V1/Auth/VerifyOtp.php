<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Contracts\LoginUser;
use App\Actions\Contracts\VerifyOtp as VerifyOtpInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\VerifyOtpRequest;

class VerifyOtp extends Controller
{
    public function __invoke(VerifyOtpRequest $request, VerifyOtpInterface $verifyOtp, LoginUser $loginUser)
    {
        $user = $verifyOtp->handle($request->validated('vid'), $request->validated('code'), $request);

        return $this->successResponse(
            $loginUser->handle($user, $request->validated('source'), $request)
        );
    }
}
