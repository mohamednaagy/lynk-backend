<?php

namespace App\Actions\Contracts\Lenders\Auth;

use App\Models\User;

interface RegisterLender
{
    /**
     * @param  array  $data
     * @return User
     */
    public function handle(array $data): User;
}
