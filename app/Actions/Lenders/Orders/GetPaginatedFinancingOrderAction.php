<?php

namespace App\Actions\Lenders\Orders;

use App\Actions\Contracts\Lenders\Orders\GetPaginatedFinancingOrder;
use App\Models\FinancingOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedFinancingOrderAction implements GetPaginatedFinancingOrder
{
    public function handle($paginate = 10): LengthAwarePaginator
    {
        return FinancingOrder::paginate($paginate);
    }
}
