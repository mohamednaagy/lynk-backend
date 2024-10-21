<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface PartiallyUpdateAdmin
{

    /**
     * Partially update user.
     *
     * @param  array  $data
     * @param  User  $user
     * @return void
     */
    public function handle(array $data, User $user): bool;
}
