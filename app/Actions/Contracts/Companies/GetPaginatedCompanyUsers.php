<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedCompanyUsers
{
    /**
     * @param  Company  $company
     * @return LengthAwarePaginator
     */
    public function handle(Company $company): LengthAwarePaginator;
}
