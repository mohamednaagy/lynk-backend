<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetPaginatedTraders;
use App\Enums\CompanyType;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedTradersAction implements GetPaginatedTraders
{
    public function handle(): LengthAwarePaginator
    {
        return Company::query()
            ->type(CompanyType::Trader)
            ->selectTraderOrdersCountBySubquery()
            ->addSelect('companies.*')
            ->paginate();
    }
}
