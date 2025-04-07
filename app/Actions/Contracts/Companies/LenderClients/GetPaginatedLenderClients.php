<?php

namespace App\Actions\Contracts\Companies\LenderClients;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedLenderClients
{
    public function handle(Company $lender): LengthAwarePaginator;
}
