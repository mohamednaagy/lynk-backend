<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendLinkRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /**
     * @param  SendLinkRequest  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById
     */
    public function __invoke(SendLinkRequest $request)
    {
        $company = null;

        if (($companyUniqueName = $request->safeInput('company_unique_name')) != null) {
            $company = Company::where('unique_name', $companyUniqueName)->firstOrFail();
            tenancy()->initialize($company);
        }

        $status = Password::sendResetLink([
            'email' => $request->only('email'),
            function ($query) use ($company) {
                if ($company === null) {
                    $query->whereNull('company_id');
                }
            },
        ]);

        return $status === Password::RESET_LINK_SENT
        ? $this->successResponse([
            'message' => trans($status),
        ])
        : throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
