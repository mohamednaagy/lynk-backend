<?php

namespace App\Actions\Traders;

use App\Actions\Contracts\Traders\GetPaginatedTraderUsers;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedTraderUsersAction implements GetPaginatedTraderUsers
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                return $query->whereIn('name', [
                    Role::TraderAdmin,
                ]);
            })
            ->with('permissions', 'roles')
            ->paginate();
    }
}
