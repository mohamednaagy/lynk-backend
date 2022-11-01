<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Models\FinancingOrder;
use App\Support\QueryScoper\Scopes\Lender\Orders\OrderNeedActionScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedFinancingOrderAction implements GetPaginatedFinancingOrder
{
    public function handle($paginate = 10): LengthAwarePaginator
    {
        return FinancingOrder::toScopes($this->scopes())->paginate($paginate);
    }

    private function scopes()
    {
        return [
            'need_action' => new OrderNeedActionScope,
        ];
    }
}
