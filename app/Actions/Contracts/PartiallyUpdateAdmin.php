<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface PartiallyUpdateAdmin
{
    /**
     * Partially update user.
     *
     * @return void
     */
    public function handle(array $data, User $user): bool;
}
