<?php

namespace App\Actions\Contracts\Admins;

use App\Models\User;

interface AddAdminToAssignedList
{
    public function handle(User $user): User;
}
