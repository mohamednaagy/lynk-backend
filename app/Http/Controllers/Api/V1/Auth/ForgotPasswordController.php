<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendLinkRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Password;


class ForgotPasswordController extends Controller
{
    public function sendResetPasswordLink(SendLinkRequest $request)
    {
        if ($request['company_unique_name'] != null) {
            $company = Company::where('unique_name', $request->company_unique_name)->firstOrFail();
            tenancy()->initialize($company);
        }
        $status = Password::sendResetLink(
            $request->only('email')
        );
        return $status === Password::RESET_LINK_SENT
            ? $this->successResponse(['status' => __($status)])
            : $this->errorResponse();
    }
}
