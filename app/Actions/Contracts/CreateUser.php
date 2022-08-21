<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface CreateUser
{
    /**
     * @param array $data
     * @return User
     */
    public function handle(array $data): User;
}
