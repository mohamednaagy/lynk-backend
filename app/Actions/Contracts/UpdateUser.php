<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface UpdateUser
{
    public function handle(User $user, array $data): bool;
}
