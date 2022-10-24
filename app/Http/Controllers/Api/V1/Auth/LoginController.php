<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Contracts\LoginUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  LoginRequest  $request
     * @param  LoginUser  $loginUser
     * @return JsonResponse
     *
     * @throws ValidationException
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function authenticate(LoginRequest $request, LoginUser $loginUser)
    {
        if (($companyUniqueName = $request->safeInput('unique_name')) != null) {
            $company = Company::where('unique_name', $companyUniqueName)->firstOrFail();
            tenancy()->initialize($company);
        }

        $user = User::where('email', $request->safeInput('email'))->first();

        if ($user === null || ! Hash::check($request->safeInput('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $this->successResponse(
            $loginUser->handle($user, $request->input('source'), $request)
        );
    }

    /**
     * Handle logout attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        if (EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } else {
            $request->user()->currentAccessToken()->delete();
        }

        return $this->successResponse(statusCode: Response::HTTP_NO_CONTENT);
    }
}
