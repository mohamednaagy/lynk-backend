<?php

namespace App\Actions\Contracts\Traders\Auth;

use App\Models\User;

interface CompleteUserRegistration
{
    public function handle(User $user, array $data): User;
}
