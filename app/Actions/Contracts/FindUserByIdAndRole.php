<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface FindUserByIdAndRole
{
    /**
     * @param  int  $id
     * @param  string  $role
     * @return User|null
     */
    public function handle(int $id, string $role): ?User;
}
