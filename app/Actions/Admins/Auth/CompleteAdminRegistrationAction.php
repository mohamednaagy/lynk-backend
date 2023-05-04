<?php

namespace App\Actions\Admins\Auth;

use App\Actions\Contracts\Admins\Auth\CompleteAdminRegistration;
use App\Models\User;

class CompleteAdminRegistrationAction implements CompleteAdminRegistration
{
    public function handle(User $user, $data): User
    {
        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'password' => $data['password'],
        ]);

        $user->markEmailAsVerified();

        return $user;
    }
}
