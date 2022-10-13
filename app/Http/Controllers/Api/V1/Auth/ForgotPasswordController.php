<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class ForgotPasswordController extends Controller
{
    public function sendResetPasswordLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', Rule::exists(User::class, 'email')],
            'unique_name' => ['nullable', 'string', Rule::exists(Company::class, 'unique_name')]
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );
        return $status === Password::RESET_LINK_SENT
            ? $this->successResponse(['status' => __($status)])
            : $this->errorResponse();
    }
}
