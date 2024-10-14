<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendLinkRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById
     */
    public function __invoke(SendLinkRequest $request)
    {
        $company = null;

        if (($companyUniqueName = $request->validated('company_unique_name')) != null) {
            $companyType = $request->validated('company_type');
            $company = Company::type($companyType)->where('unique_name', $companyUniqueName)->first();
            if ($company) {
                tenancy()->initialize($company);
            } else {
                return $this->successResponse();
            }
        }

        Password::sendResetLink([
            'email' => $request->only('email'),
            function ($query) use ($company) {
                if ($company === null) {
                    $query->whereNull('company_id');
                }
            },
        ]);

        return $this->successResponse();
    }
}
