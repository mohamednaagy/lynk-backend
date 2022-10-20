<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Models\FinancingOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedFinancingOrderAction implements GetPaginatedFinancingOrder
{
    public function handle($paginate = 10): LengthAwarePaginator
    {
        return FinancingOrder::paginate($paginate);
    }
}
