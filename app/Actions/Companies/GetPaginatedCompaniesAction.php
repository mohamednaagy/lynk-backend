<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetPaginatedCompanies;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedCompaniesAction implements GetPaginatedCompanies
{
    public function handle($companyType = null): LengthAwarePaginator
    {
        return Company::query()->when($companyType, function ($query) use ($companyType) {
            $query->where('type', $companyType);
        })->withCount('orders')->paginate();
    }
}
