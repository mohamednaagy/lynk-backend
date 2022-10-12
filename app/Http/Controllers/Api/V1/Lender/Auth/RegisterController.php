<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\LoginUser;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\RegisterRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Modules\Grantify\Facades\Grantify;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, LoginUser $loginUser)
    {
        $validated = $request->safe();

        $validated['password'] = Hash::make($validated['password']);
        $validated['phone_number'] = phone($validated['phone_number'], $validated['phone_country_code']);

        $company = Company::create(
            [
                'name' => $validated->company_name,
                'unique_name' => $validated->company_unique_name,
                'company_cr' => $validated->company_cr
            ]
        );

        tenancy()->initialize($company);

        $user = User::create(
            $validated->only([
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'password',
                'source',
            ])
        );

        Grantify::assignRoleToModel($user, Role::LenderAdmin);
        return $this->successResponse(
            $loginUser->handle($user, $validated['source'], $request),
            Response::HTTP_CREATED
        );
    }
}
