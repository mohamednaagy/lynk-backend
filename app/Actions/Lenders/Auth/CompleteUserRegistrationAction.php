<?php

namespace App\Actions\Lenders\Auth;

use App\Actions\Contracts\Lenders\Auth\CompleteUserRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CompleteUserRegistrationAction implements CompleteUserRegistration
{
    public function handle(User $user, $data): User
    {
        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'password' => Hash::make($data['password']),
        ]);

        return $user;
    }
}
