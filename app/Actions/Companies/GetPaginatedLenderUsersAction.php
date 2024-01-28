<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetPaginatedLenderUsers;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedLenderUsersAction implements GetPaginatedLenderUsers
{
    public function handle(Company $lender): LengthAwarePaginator
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                return $query->whereIn('name', [
                    Role::LenderAdmin,
                    Role::LenderOrderCreator,
                    Role::LenderBilling,
                    Role::LenderSupervisor,
                    Role::LenderApiAdmin,
                ]);
            })
            ->where('company_id', $lender->id)
            ->withCount('orders')
            ->with('permissions', 'roles')
            ->paginate();
    }
}
