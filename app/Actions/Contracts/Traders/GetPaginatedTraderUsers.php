<?php

namespace App\Actions\Contracts\Traders;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedTraderUsers
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator;

    public function setTrader(Company $trader);
}
