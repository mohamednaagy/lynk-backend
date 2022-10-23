<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Company;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class ResetPasswordController extends Controller
{
    /**
     * @param  ResetPasswordRequest  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws TenantCouldNotBeIdentifiedById
     * @throws ValidationException
     */
    public function __invoke(ResetPasswordRequest $request)
    {
        $company = null;

        if (($companyUniqueName = $request->safeInput('company_unique_name')) != null) {
            $company = Company::where('unique_name', $companyUniqueName)->first();
            tenancy()->initialize($company);
        }

        Password::broker('users')->reset(
            $request->validated(
                ['email', 'password', 'password_confirmation', 'token']
            ) + [
                function ($query) use ($company) {
                    if ($company === null) {
                        $query->whereNull('company_id');
                    }
                },
            ],
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
