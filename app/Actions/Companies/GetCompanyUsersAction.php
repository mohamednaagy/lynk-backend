<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetCompanyUsers;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetCompanyUsersAction implements GetCompanyUsers
{
    /**
     * @param  Company  $company
     * @return LengthAwarePaginator
     */
    public function handle(Company $company): LengthAwarePaginator
    {
        return $company->users()->paginate();
    }
}
