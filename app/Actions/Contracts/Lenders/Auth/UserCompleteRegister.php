<?php

namespace App\Actions\Contracts\Lenders\Auth;

use App\Models\User;

interface UserCompleteRegister
{
    public function handle(User $user, array $data) : User;
}
