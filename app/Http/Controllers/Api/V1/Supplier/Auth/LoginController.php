<?php

namespace App\Http\Controllers\Api\V1\Supplier\Auth;

use App\Actions\Contracts\LoginUser;
use App\Actions\Contracts\SendOtp;
use App\Enums\CommoitySupplierStatus;
use App\Enums\CompanyType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\Auth\LoginRequest;
use App\Models\Company;
use App\Models\User;
use App\Notifications\LoginNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
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
        $company = Company::where('unique_name', $companyUniqueName)->where('type', CompanyType::Supplier)->firstOrFail();
        tenancy()->initialize($company);
        $user = User::where('email', $request->validated('email'))->where('company_id', $company->id)->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($user->company->commoditySupplier->status->value != CommoitySupplierStatus::Active) {
            throw ValidationException::withMessages([
                'unique_name' => __('auth.inactive_supplier_status'),
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
}
