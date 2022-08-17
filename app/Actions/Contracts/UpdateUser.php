<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface UpdateUser
{
    /**
     * @param User $user
     * @param array $data
     * @return bool
     */
    public function __invoke(User $user, array $data): bool;
}
