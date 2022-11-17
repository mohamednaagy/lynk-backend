<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Models\FinancingOrder;
use App\Support\QueryScoper\Scopes\Lender\Orders\OrderNeedActionScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedFinancingOrderAction implements GetPaginatedFinancingOrder
{
    public function handle($perPage = null): LengthAwarePaginator
    {
        return FinancingOrder::toScopes($this->scopes())->paginate($perPage);
    }

    private function scopes()
    {
        return [
            'need_action' => new OrderNeedActionScope(),
        ];
    }
}
