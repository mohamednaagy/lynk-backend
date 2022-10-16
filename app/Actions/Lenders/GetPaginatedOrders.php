<?php
namespace App\Actions\Lenders;

use App\Models\FinancingOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Actions\Contracts\Lenders\GetPaginatedOrders as LendersGetPaginatedOrders;

class GetPaginatedOrders implements LendersGetPaginatedOrders
{
    public function handle($paginate = 10): LengthAwarePaginator
    {
        return FinancingOrder::paginate($paginate);
    }
}
