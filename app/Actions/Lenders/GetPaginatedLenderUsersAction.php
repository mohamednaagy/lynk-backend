<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetPaginatedLenderUsers;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedLenderUsersAction implements GetPaginatedLenderUsers
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                return $query->whereIn('name', [
                    Role::LenderAdmin,
                    Role::LenderOrderCreator,
                    Role::LenderBilling,
                    Role::LenderSupervisor,
                ]);
            })
            ->with('permissions')
            ->paginate();
    }
}
