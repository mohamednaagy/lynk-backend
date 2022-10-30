<?php

namespace App\Actions\Contracts\Admins\Auth;

use App\Models\User;

interface RegisterAdmin
{
    /**
     * @param  array  $data
     * @return User
     */
    public function handle(array $data): User;
}
