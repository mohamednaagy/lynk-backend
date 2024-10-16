<?php

namespace App\Actions\Contracts\Admins;

use App\Models\User;

interface RemoveAdminFromAssignedList
{
    /**
     * @return User
     */
    public function handle(User $user): User;
}
