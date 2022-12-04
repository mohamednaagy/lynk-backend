<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface GetPaginatedFinancingOrder
{
    public function handle($paginate = null): LengthAwarePaginator;

    public function setCreator(Model $creator);

    public function setCompany(Company $company);
}
