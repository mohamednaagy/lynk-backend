<?php

namespace App\Actions\Users;

use App\Actions\Contracts\Users\GetPaginatedUsers;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedUsersAction implements GetPaginatedUsers
{
    /**
     * @param $companyType
     * @param  array|null  $role
     * @return LengthAwarePaginator
     */
    public function handle($companyType, array $role = null): LengthAwarePaginator
    {
        $users = User::query()->companyType($companyType);

        if ($role) {
            $users->whereHas('roles', function ($query) use ($role) {
                return $query->whereIn('name', $role);
            });
        }

        return $users->with('permissions', 'roles')
            ->paginate();
    }
}
