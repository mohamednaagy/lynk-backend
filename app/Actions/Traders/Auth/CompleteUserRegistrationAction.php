<?php

namespace App\Actions\Traders\Auth;

use App\Actions\Contracts\Traders\Auth\CompleteUserRegistration;
use App\Models\User;

class CompleteUserRegistrationAction implements CompleteUserRegistration
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
