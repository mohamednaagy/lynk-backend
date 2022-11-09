<?php

namespace App\Actions;

use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedUsersByRoleAction implements GetPaginatedUsersByRole
{
    /**
     * @param  string|array  $role
     * @return LengthAwarePaginator
     */
    public function handle(string|array $role): LengthAwarePaginator
    {
        return User::role($role)->paginate();
    }
}
