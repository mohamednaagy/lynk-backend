<?php

namespace App\Actions\Contracts\Lenders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedOrders
{
    public function handle($paginate = 10): LengthAwarePaginator;
}
