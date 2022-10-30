<?php

namespace App\Actions\Contracts\Admins\Auth;

use App\Models\User;

interface CompleteAdminRegistration
{
    public function handle(User $user, array $data): User;
}
