<?php

namespace Modules\Admin\Actions\Admins;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdmin
{
    /**
     * Create new user.
     * @param array $data
     * @return User
     */
    public function handle(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code']);

        $user = User::create($data);

        if (!empty($data['role']))
            \Grantify::assignRoleToModel($user, $data['role']);

        if (!empty($data['permissions']))
            \Grantify::assignPermissionToModel($user, $data['permissions']);

        return $user;
    }
}
