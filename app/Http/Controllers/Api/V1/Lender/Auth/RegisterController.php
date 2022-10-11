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

        $company = Company::create(
            [
                'name' => $validated->company_name,
                'company_cr' => $validated->company_cr
            ]
        );

        tenancy()->initialize($company);

        $user = User::create(
            $validated->only([
                'first_name',
                'last_name',
                'email',
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
