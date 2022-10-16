<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendLinkRequest;
use Illuminate\Support\Facades\Password;


class ForgotPasswordController extends Controller
{
    public function sendResetPasswordLink(SendLinkRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email', 'company_name')
        );
        return $status === Password::RESET_LINK_SENT
            ? $this->successResponse(['status' => __($status)])
            : $this->errorResponse();
    }
}
