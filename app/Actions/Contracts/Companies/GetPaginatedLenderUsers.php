<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedLenderUsers
{
    public function handle(Company $lender): LengthAwarePaginator;
}
