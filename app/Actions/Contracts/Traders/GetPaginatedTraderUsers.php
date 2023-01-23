<?php

namespace App\Actions\Contracts\Traders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedTraderUsers
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator;
}
