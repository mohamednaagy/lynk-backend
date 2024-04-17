<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Contracts\LoginUser;
use App\Actions\Contracts\SendOtp;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Models\Company;
use App\Models\User;
use App\Notifications\LoginNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Jenssegers\Agent\Facades\Agent;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:5,1')->only('authenticate');
    }

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

        if (! $user->hasRole(Role::LenderApiUser)) {
            $user->notify(
                new LoginNotification(
                    $request->ip(),
                    Carbon::now()->toDateTimeString(),
                    Agent::device(),
                    Agent::platform(),
                    Agent::browser()
                )
            );
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

        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(statusCode: Response::HTTP_NO_CONTENT);
    }
}
