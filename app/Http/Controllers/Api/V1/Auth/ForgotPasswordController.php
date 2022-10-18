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
        if (($companyUniqueName = $request->safeInput('company_unique_name')) != null) {
            $company = Company::where('unique_name', $companyUniqueName)->firstOrFail();
            tenancy()->initialize($company);
        }

        Password::sendResetLink(
            $request->only('email')
        );

        return $this->successResponse();
    }
}
