<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Models\FinancingOrder;
use App\Support\QueryScoper\Scopes\Lender\Orders\OrderNeedActionScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class GetPaginatedFinancingOrderAction implements GetPaginatedFinancingOrder
{
    protected ?Model $creator = null;

    public function handle($paginate = 10): LengthAwarePaginator
    {
        return $this->baseQuery()->toScopes($this->scopes())->paginate($paginate);
    }

    private function scopes()
    {
        return [
            'need_action' => new OrderNeedActionScope(),
        ];
    }

    public function setCreator(Model $creator)
    {
        $this->creator = $creator;

        return $this;
    }

    protected function baseQuery()
    {
        return FinancingOrder::when(
            $this->creator,
            function ($query) {
                $query->byCreator($this->creator);
            }
        );
    }
}
