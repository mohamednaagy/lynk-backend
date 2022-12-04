<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderAmountScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderNeedActionScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderSearchScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderSortScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderStatusScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class GetPaginatedFinancingOrderAction implements GetPaginatedFinancingOrder
{
    protected ?Model $creator = null;

    protected ?Company $company = null;

    public function handle($paginate = null): LengthAwarePaginator
    {
        return $this->baseQuery()->toScopes($this->scopes())->paginate();
    }

    private function scopes()
    {
        return [
            'need_action' => new OrderNeedActionScope(),
            'search' => new OrderSearchScope(),
            'status' => new OrderStatusScope(),
            'sort' => new OrderSortScope(),
            'amount' => new OrderAmountScope(),
        ];
    }

    public function setCreator(Model $creator)
    {
        $this->creator = $creator;

        return $this;
    }

    public function setCompany(Company $company)
    {
        $this->company = $company;

        return $this;
    }

    protected function baseQuery()
    {
        return FinancingOrder::when(
            $this->creator,
            function ($query) {
                $query->byCreator($this->creator);
            }
        )->when(
            $this->company,
            function ($query) {
                $query->with('creator')
                    ->where('company_id', $this->company->id);
            }
        );
    }
}
