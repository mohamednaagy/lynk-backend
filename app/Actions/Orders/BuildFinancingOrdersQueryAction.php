<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderAmountScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderCompanyScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderFilterScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderNeedActionScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderSearchScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderSortScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\OrderStatusScope;
use App\Support\QueryScoper\Scopes\FinancingOrders\TraderOrderCurrentStepScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\TenantScope;

class BuildFinancingOrdersQueryAction implements BuildFinancingOrdersQuery
{
    protected ?Model $creator = null;

    protected ?Company $company = null;

    private ?array $relations = [];

    public function handle(): Builder
    {
        return $this->baseQuery()
            ->with($this->relations)
            ->toScopes($this->scopes());
    }

    private function scopes(): array
    {
        return [
            'need_action' => new OrderNeedActionScope(),
            'search' => new OrderSearchScope(),
            'status' => new OrderStatusScope(),
            'sort' => new OrderSortScope(),
            'amount' => new OrderAmountScope(),
            'current_step' => new TraderOrderCurrentStepScope(),
            'filter' => new OrderFilterScope(),
            'company' => new OrderCompanyScope(),
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

    public function setRelations(array $relations): static
    {
        $this->relations = $relations;

        return $this;
    }

    protected function baseQuery()
    {
        $baseQuery = FinancingOrder::query();

        if ($this->creator) {
            $baseQuery->byCreator($this->creator);
        }

        if ($this->company?->type?->is(CompanyType::Trader)) {
            $baseQuery->withoutGlobalScope(TenantScope::class)
                ->withWhereHas('traderOrders', function ($query) {
                    $query->where('provider', $this->company->driver);
                });
        }

        if ($this->company?->type?->is(CompanyType::Lender)) {
            $baseQuery->with([
                'creator',
                'activeTraderOrder' => fn ($query) => $query->withLastHistoryAction()->latest(),
            ])
                ->where('company_id', $this->company->id);
        }

        return $baseQuery;
    }
}
