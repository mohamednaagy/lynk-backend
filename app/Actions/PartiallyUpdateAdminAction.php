<?php

namespace App\Actions;

use App\Actions\Contracts\PartiallyUpdateAdmin;
use App\Models\User;
use DragonCode\Support\Facades\Helpers\Arr;

class PartiallyUpdateAdminAction implements PartiallyUpdateAdmin
{
    /**
     * Create new user.
     *
     * @param  array  $data
     * @param  User  $user
     * @return void
     */
    public function handle(array $data, User $user): bool
    {
        // update user
        return $user->update(
            Arr::only(
                $data,
                [
                    'can_manage_orders',
                ]
            )
        );
    }
}
