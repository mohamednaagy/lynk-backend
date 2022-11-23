<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetPaginatedCompanies;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedCompaniesAction implements GetPaginatedCompanies
{
    public function handle(): LengthAwarePaginator
    {
        return Company::query()->withCount('orders')->paginate();
    }
}
