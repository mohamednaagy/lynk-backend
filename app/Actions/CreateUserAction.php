<?php

namespace App\Actions;

use App\Actions\Contracts\CreateUser;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class CreateUserAction implements CreateUser
{
    /**
     * @param  array  $data
     * @return User
     */
    public function handle(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code']);

        return User::create(
            Arr::only(
                $data,
                [
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'password',
                    'source',
                ]
            )
        );
    }
}
