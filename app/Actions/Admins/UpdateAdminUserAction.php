<?php

namespace App\Actions\Admins;

use App\Actions\Contracts\Admins\UpdateAdminUser;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class UpdateAdminUserAction implements UpdateAdminUser
{
    /**
     * @param  User  $user
     * @param  array  $data
     * @return bool
     */
    public function handle(User $user, array $data): bool
    {
        if (array_key_exists('password', $data) && ! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            $data['password'] = $user->password;
        }

        return $user->update(
            Arr::only(
                $data,
                [
                    'first_name',
                    'last_name',
                    'email',
                    'password',
                ]
            )
        );
    }
}
