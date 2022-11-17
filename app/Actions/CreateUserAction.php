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
        if (array_key_exists('password', $data) && $data['password'] !== null) {
            $data['password'] = Hash::make($data['password']);
        } else {
            $data['password'] = null;
        }

        if (array_key_exists('phone_number', $data) && array_key_exists('phone_country_code', $data)) {
            $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code']);
        } else {
            $data['phone_number'] = null;
        }

        // __REVIEW__ source should be removed
        // __REVIEW__ locale should be added with a default value if it doesn't exist
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
