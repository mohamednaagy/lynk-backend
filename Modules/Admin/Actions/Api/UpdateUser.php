<?php

namespace Modules\Admin\Actions\Api;

use App\Models\User;
use Illuminate\Http\Request;

class UpdateUser
{
    /**
     * Create new user.
     * @param Request $request
     * @param User $user
     * @return void
     */
    public function handle(Request $request, User $user): void
    {
        $validated = $request->validated();
        $validated['phone_number'] = phone($request->input('phone_number'), $request->input('phone_country_code'))->formatE164();
        $user->update($validated);

        if (!empty($request->input('role')))
            \Grantify::syncRoleToModel($user, $request->input('role'));

        if (!empty($request->input('permissions')))
            \Grantify::syncPermissionToModel($user, $request->input('permissions'));
    }
}
