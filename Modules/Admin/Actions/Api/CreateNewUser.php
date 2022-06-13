<?php

namespace Modules\Admin\Actions\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CreateNewUser
{
    /**
     * Create new user.
     * @param Request $request
     * @return User
     */
    public function handle(Request $request): User
    {
        $validated = $request->validated();

        $validated['password'] = Hash::make($validated['password']);
        $validated['phone_number'] = phone($validated['phone_number'], $request->input('phone_country_code'));

        $user = User::create($validated);

        if (!empty($request->input('role')))
            \Grantify::assignRoleToModel($user, $request->input('role'));

        if (!empty($request->input('permissions')))
            \Grantify::assignPermissionToModel($user, $request->input('permissions'));

        return $user;
    }
}
