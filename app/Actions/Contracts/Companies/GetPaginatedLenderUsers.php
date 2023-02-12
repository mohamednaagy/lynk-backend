<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedLenderUsers
{
    /**
     * @param  Company  $lender
     * @return LengthAwarePaginator
     */
    public function handle(Company $lender): LengthAwarePaginator;
}
