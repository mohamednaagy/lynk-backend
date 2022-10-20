<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Company;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function resetPassword(ResetPasswordRequest $request)
    {
        $company = null;

        if (($companyUniqueName = $request->safeInput('company_unique_name')) != null) {
            $company = Company::where('unique_name', $companyUniqueName)->firstOrFail();
            tenancy()->initialize($company);
        }

        Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),

            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $this->successResponse();
    }
}
