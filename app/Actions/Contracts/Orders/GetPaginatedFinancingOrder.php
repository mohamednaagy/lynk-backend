<?php

namespace App\Actions\Contracts\Orders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedFinancingOrder
{
    public function handle($paginate = 10): LengthAwarePaginator;
}
