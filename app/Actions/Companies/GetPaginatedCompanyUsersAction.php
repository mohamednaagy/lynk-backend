<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetPaginatedCompanyUsers;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedCompanyUsersAction implements GetPaginatedCompanyUsers
{
    /**
     * @param  Company  $company
     * @return LengthAwarePaginator
     */
    public function handle(Company $company): LengthAwarePaginator
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
            ->where('company_id', $company->id)
            ->withCount(['orders', 'roles'])
            ->paginate();
    }
}
