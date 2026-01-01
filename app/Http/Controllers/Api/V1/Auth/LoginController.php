<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Contracts\LoginUser;
use App\Actions\Contracts\SendOtp;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @return JsonResponse
     *
     * @throws ValidationException
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function authenticate(LoginRequest $request, LoginUser $loginUser, SendOtp $sendOtp)
    {
        $companyUniqueName = $request->validated('unique_name');
        $company = null;

        if (! is_null($companyUniqueName)) {
            $company = Company::where('unique_name', $companyUniqueName)->firstOrFail();
            tenancy()->initialize($company);
        }

        $user = User::where('email', $request->validated('email'))
            ->when(is_null($company), function ($query) {
                $query->whereNull('company_id');
            })->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($otpCode = $sendOtp->handle($user, $request)) {
            return $this->successResponse([
                'vid' => $otpCode->id,
            ]);
        }

        return $this->successResponse(
            $loginUser->handle($user, $request->validated('source'), $request)
        );
    }

    /**
     * Handle logout attempt.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function logout(Request $request)
    {
        if ($companyId = $request->user()->company_id) {
            tenancy()->initialize($companyId);
        }
        Cache::forget('has_verified_otp_'.$request->user()->id);
        JWTAuth::invalidate(JWTAuth::getToken());

        return $this->successResponse(statusCode: Response::HTTP_NO_CONTENT);
    }
}
