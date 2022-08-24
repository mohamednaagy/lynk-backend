<?php

namespace App\Actions;

use App\Models\User;
use App\Actions\Contracts\GetPaginatedUsersByRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedUsersByRoleAction implements GetPaginatedUsersByRole
{
    /**
     * @param string $role
     * @return LengthAwarePaginator
     */
    public function handle(string $role): LengthAwarePaginator
    {
       return User::role($role)->paginate();
    }
}
