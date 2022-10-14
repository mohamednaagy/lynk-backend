<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\sendResetPasswordLinkRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;


class ForgotPasswordController extends Controller
{
    public function sendResetPasswordLink(sendResetPasswordLinkRequest $request)
    {

        $status = Password::sendResetLink(
            $request->only('email')
        );
        return $status === Password::RESET_LINK_SENT
            ? $this->successResponse(['status' => __($status)])
            : $this->errorResponse();
    }
}
