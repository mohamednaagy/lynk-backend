<?php

namespace App\Actions;

use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GetPaginatedUsersByRoleAction implements GetPaginatedUsersByRole
{
    /**
     * @param  string|array  $role
     * @return Builder
     */
    public function handle(string|array $role): Builder
    {
        return User::role($role)
            ->with('roles', 'permissions');
    }
}
