<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Actions\Contracts\CreateUser;

class CreateUserAction implements CreateUser
{
    /**
     * @param array $data
     * @return User
     */
    public function __invoke(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code']);

        return User::create($data);
    }
}
