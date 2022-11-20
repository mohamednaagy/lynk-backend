<?php

namespace App\Actions\Admins\Auth;

use App\Actions\Contracts\Admins\Auth\CompleteAdminRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CompleteAdminRegistrationAction implements CompleteAdminRegistration
{
    public function handle(User $user, $data): User
    {
        // __REVIEW__ use UpdateUserAction
        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'password' => Hash::make($data['password']),
        ]);

        // __REVIEW__ after completing registration, mark user email as verified

        return $user;
    }
}
