<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetCompanyUsers;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetCompanyUsersAction implements GetCompanyUsers
{
    /**
     * @param  int  $companyId
     * @return LengthAwarePaginator
     */
    public function handle(int $companyId): LengthAwarePaginator
    {
        return User::query()->where('company_id', $companyId)->paginate();
    }
}
