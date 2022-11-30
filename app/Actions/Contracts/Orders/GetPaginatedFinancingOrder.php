<?php

namespace App\Actions\Contracts\Orders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface GetPaginatedFinancingOrder
{
    public function handle($paginate = 10): LengthAwarePaginator;

    public function setCreator(Model $creator);
}
