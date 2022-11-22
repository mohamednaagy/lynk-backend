<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetCompanyUsers;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetCompanyUsersAction implements GetCompanyUsers
{
    /**
     * @param  Company  $company
     * @return LengthAwarePaginator
     */
    public function handle(Company $company): LengthAwarePaginator
    {
        // __REVIEW__ add number of orders created by each
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
            ->paginate();
    }
}
