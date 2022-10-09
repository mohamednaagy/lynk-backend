<?php

namespace App\Http\Controllers\Api\V1\Customers\Auth;

use App\Actions\Contracts\LoginUser;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Modules\Grantify\Facades\Grantify;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, LoginUser $loginUser)
    {
        $validated = $request->safe()->only([
            'first_name',
            'last_name',
            'phone_number',
            'phone_country_code',
            'email',
            'password',
            'source',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['phone_number'] = phone($validated['phone_number'], $validated['phone_country_code']);

        $user = User::create($validated);

        Grantify::assignRoleToModel($user, Role::Customer);

        return $this->successResponse(
            $loginUser->handle($user, $validated['source'], $request),
            Response::HTTP_CREATED
        );
    }
}
