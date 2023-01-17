<?php

namespace App\Actions\Contracts\Companies;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedCompanies
{
    public function handle($companyType = null): LengthAwarePaginator;
}
