<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetCompanies;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetCompaniesAction implements GetCompanies
{
    public function handle(): LengthAwarePaginator
    {
        return Company::query()->paginate();
    }
}
