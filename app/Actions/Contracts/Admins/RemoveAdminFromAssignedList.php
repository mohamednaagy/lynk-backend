<?php

namespace App\Actions\Contracts\Admins;

use App\Models\User;

interface RemoveAdminFromAssignedList
{
    public function handle(User $user): User;
}
