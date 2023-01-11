<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderAmountScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderNeedActionScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderSearchScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderSortScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderStatusScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\TenantScope;

class GetPaginatedFinancingOrderAction implements GetPaginatedFinancingOrder
{
    protected ?Model $creator = null;

    protected ?Company $company = null;

    protected string $trader;

    public function handle($perPage = null): LengthAwarePaginator
    {
        return $this->baseQuery()->toScopes($this->scopes())->paginate($perPage);
    }

    private function scopes(): array
    {
        return [
            'need_action' => new OrderNeedActionScope(),
            'search' => new OrderSearchScope(),
            'status' => new OrderStatusScope(),
            'sort' => new OrderSortScope(),
            'amount' => new OrderAmountScope(),
        ];
    }

    public function setCreator(Model $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function setTrader(string $trader): static
    {
        $this->trader = $trader;

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
        )->when(
            filled($this->trader) && tenant()->type->is(CompanyType::Trader),
            function ($query) {
                $query->withoutGlobalScope(TenantScope::class)
                    ->withWhereHas('activeTraderOrder', function ($query) {
                        $query->where('provider', Str::lower($this->trader));
                    });
            }
        );
    }
}
