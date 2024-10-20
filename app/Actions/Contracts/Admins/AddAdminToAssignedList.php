<?php

namespace App\Actions\Contracts\Admins;

use App\Models\User;

interface AddAdminToAssignedList
{
    /**
     * @return User
     */
    public function handle(User $user): User;
}
